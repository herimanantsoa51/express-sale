from __future__ import annotations

import logging
from typing import Any, TypedDict

from langgraph.graph import END, StateGraph

from langgraph_service.app.chains import (
    build_response,
    detect_language_with_models,
    get_store,
    has_route_intent,
    _detect_db_intent,
    _detect_db_intent_from_context,
    _run_db_query,
)

logger = logging.getLogger("langgraph_service.graph")


class GraphState(TypedDict, total=False):
    input: str
    top_k: int
    context: list[str]
    debug: bool
    language: str
    fr_score: float
    mg_score: float
    faq_hit: bool
    faq_score: float
    faq_answer: str
    db_hit: bool
    db_result: dict[str, Any]
    rag_docs: list[dict[str, Any]]
    response: str
    source: str
    next_route: str
    trace: list[dict[str, Any]]


def _append_trace(state: GraphState, step: str, details: dict[str, Any]) -> list[dict[str, Any]]:
    base = list(state.get("trace", []))
    base.append({"step": step, **details})
    return base


def _effective_query(state: GraphState) -> str:
    history = state.get("context") or []
    if not history:
        return state["input"]
    recent = [str(item).strip() for item in history[-3:] if str(item).strip()]
    if not recent:
        return state["input"]
    return "\n".join(recent + [state["input"]])


def detect_language(state: GraphState) -> GraphState:
    lang, fr_score, mg_score = detect_language_with_models(state["input"])
    logger.info("detect_language lang=%s fr=%.4f mg=%.4f", lang, fr_score, mg_score)
    return {
        "language": lang,
        "fr_score": fr_score,
        "mg_score": mg_score,
        "trace": _append_trace(
            state,
            "detect_language",
            {"language": lang, "fr_score": fr_score, "mg_score": mg_score},
        ),
    }


def retrieve_faq(state: GraphState) -> GraphState:
    if has_route_intent(state["input"]):
        logger.info("retrieve_faq skipped (route_intent=true)")
        return {
            "faq_hit": False,
            "next_route": "retrieve_rag",
            "trace": _append_trace(
                state,
                "retrieve_faq",
                {"faq_hit": False, "faq_score": 0.0, "skipped": "route_intent"},
            ),
        }

    db_intent = _detect_db_intent(state["input"])
    if not db_intent:
        db_intent = _detect_db_intent_from_context(state["input"], state.get("context") or [])
    if db_intent:
        logger.info("retrieve_faq skipped (db_intent=true)")
        table, action = db_intent
        return {
            "faq_hit": False,
            "next_route": "retrieve_db",
            "trace": _append_trace(
                state,
                "retrieve_faq",
                {"faq_hit": False, "faq_score": 0.0, "skipped": "db_intent", "table": table, "action": action},
            ),
        }

    store = get_store()
    result = store.search_faq(_effective_query(state), state.get("language", "unknown"))
    if not result:
        logger.info("retrieve_faq hit=false")
        return {
            "faq_hit": False,
            "next_route": "retrieve_rag",
            "trace": _append_trace(state, "retrieve_faq", {"faq_hit": False, "faq_score": 0.0}),
        }
    doc = result["doc"]
    score = float(result["score"])
    logger.info("retrieve_faq hit=true score=%.4f", score)
    return {
        "faq_hit": True,
        "faq_score": score,
        "faq_answer": doc.get("answer", ""),
        "source": "faq",
        "next_route": "finalize_faq",
        "trace": _append_trace(
            state,
            "retrieve_faq",
            {"faq_hit": True, "faq_score": score, "question": doc.get("question")},
        ),
    }


def route_after_faq(state: GraphState) -> str:
    return state.get("next_route") or ("finalize_faq" if state.get("faq_hit") else "retrieve_db")


def finalize_faq(state: GraphState) -> GraphState:
    logger.info("finalize_faq")
    return {
        "response": state.get("faq_answer", ""),
        "source": "faq",
        "trace": _append_trace(state, "finalize_faq", {"source": "faq"}),
    }


def retrieve_db(state: GraphState) -> GraphState:
    intent = _detect_db_intent(state["input"])
    if not intent:
        intent = _detect_db_intent_from_context(state["input"], state.get("context") or [])
    if not intent:
        return {
            "db_hit": False,
            "next_route": "retrieve_rag",
            "trace": _append_trace(state, "retrieve_db", {"db_hit": False}),
        }
    table, action = intent
    try:
        result = _run_db_query(table, action)
    except Exception as e:
        logger.info("retrieve_db error=%s", str(e))
        docs = [{
            "score": 1.0,
            "source": "db",
            "language": state.get("language", "fr"),
            "content": "",
            "data": {"error": str(e), "table": table, "action": action},
        }]
        return {
            "db_hit": True,
            "db_result": {"error": str(e), "table": table, "action": action},
            "rag_docs": docs,
            "next_route": "generate_response",
            "trace": _append_trace(state, "retrieve_db", {"db_hit": True, "table": table, "action": action, "error": str(e)}),
        }
    if not result:
        return {
            "db_hit": False,
            "next_route": "retrieve_rag",
            "trace": _append_trace(state, "retrieve_db", {"db_hit": False}),
        }
    docs = [{
        "score": 1.0,
        "source": "db",
        "language": state.get("language", "fr"),
        "content": "",
        "data": result,
    }]
    return {
        "db_hit": True,
        "db_result": result,
        "rag_docs": docs,
        "next_route": "generate_response",
        "trace": _append_trace(
            state,
            "retrieve_db",
            {"db_hit": True, "table": table, "action": action, "rows": len(result.get("rows", []))},
        ),
    }


def retrieve_rag(state: GraphState) -> GraphState:
    store = get_store()
    docs = store.search_rag(
        _effective_query(state),
        state.get("language", "unknown"),
        top_k=state.get("top_k", 3),
    )
    top_score = float(docs[0]["score"]) if docs else 0.0
    logger.info("retrieve_rag docs=%s top_score=%.4f", len(docs), top_score)
    return {
        "rag_docs": docs,
        "trace": _append_trace(
            state,
            "retrieve_rag",
            {"docs_count": len(docs), "top_score": top_score, "source": docs[0]["source"] if docs else None},
        ),
    }


def generate_response(state: GraphState) -> GraphState:
    docs = state.get("rag_docs", [])
    response = build_response(state.get("language", "fr"), state["input"], docs)
    if docs and docs[0].get("source") == "db":
        source = "db"
    else:
        source = "rag" if docs else "none"
    logger.info("generate_response source=%s", source)
    return {
        "response": response,
        "source": source,
        "trace": _append_trace(state, "generate_response", {"source": source, "response_len": len(response)}),
    }


def create_graph():
    workflow = StateGraph(GraphState)
    workflow.add_node("detect_language", detect_language)
    workflow.add_node("retrieve_faq", retrieve_faq)
    workflow.add_node("finalize_faq", finalize_faq)
    workflow.add_node("retrieve_db", retrieve_db)
    workflow.add_node("retrieve_rag", retrieve_rag)
    workflow.add_node("generate_response", generate_response)

    workflow.set_entry_point("detect_language")
    workflow.add_edge("detect_language", "retrieve_faq")
    workflow.add_conditional_edges(
        "retrieve_faq",
        route_after_faq,
        {
            "finalize_faq": "finalize_faq",
            "retrieve_db": "retrieve_db",
            "retrieve_rag": "retrieve_rag",
        },
    )
    workflow.add_edge("finalize_faq", END)
    workflow.add_conditional_edges(
        "retrieve_db",
        lambda state: state.get("next_route") or ("generate_response" if state.get("db_hit") else "retrieve_rag"),
        {
            "generate_response": "generate_response",
            "retrieve_rag": "retrieve_rag",
        },
    )
    workflow.add_edge("retrieve_rag", "generate_response")
    workflow.add_edge("generate_response", END)
    return workflow.compile()


def graph_mermaid() -> str:
    return """flowchart TD
    A([Start]) --> B[detect_language]
    B --> C[retrieve_faq]
    C -->|faq_hit=true| D[finalize_faq]
    C -->|faq_hit=false| E[retrieve_db]
    E -->|db_hit=true| F[generate_response]
    E -->|db_hit=false| G[retrieve_rag]
    G --> F
    D --> H([End])
    F --> H
"""
