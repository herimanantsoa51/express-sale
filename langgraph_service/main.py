from __future__ import annotations

import logging
from typing import Any
from typing import Literal

from fastapi import FastAPI, File, HTTPException, UploadFile
from pydantic import BaseModel, Field

from langgraph_service.app.chains import get_store
from langgraph_service.app.graph import create_graph, graph_mermaid

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s - %(message)s",
)

app = FastAPI(title="Express Sale LangGraph Service")
graph = create_graph()


class QueryRequest(BaseModel):
    text: str = Field(min_length=1, max_length=5000)
    top_k: int = Field(default=3, ge=1, le=10)
    context: list[str] | None = None
    metadata: dict[str, Any] | None = None
    debug: bool = False


class FaqItem(BaseModel):
    id: str | None = None
    question: str = Field(min_length=1)
    answer: str = Field(min_length=1)
    tags: list[str] = Field(default_factory=list)


class FaqIngestRequest(BaseModel):
    language: Literal["fr", "mg"]
    replace: bool = False
    faqs: list[FaqItem] = Field(default_factory=list)


class FaqPairItem(BaseModel):
    id: str | None = None
    question_fr: str = Field(min_length=1)
    answer_fr: str = Field(min_length=1)
    question_mg: str = Field(min_length=1)
    answer_mg: str = Field(min_length=1)
    tags: list[str] = Field(default_factory=list)


class FaqPairsIngestRequest(BaseModel):
    replace: bool = False
    faqs: list[FaqPairItem] = Field(default_factory=list)


@app.get("/health")
async def health() -> dict[str, str]:
    return {"status": "ok"}


@app.get("/graph/mermaid")
async def graph_visual_mermaid() -> dict[str, str]:
    return {"format": "mermaid", "diagram": graph_mermaid()}


@app.post("/query")
async def query(payload: QueryRequest):
    result = graph.invoke(
        {
            "input": payload.text,
            "top_k": payload.top_k,
            "context": payload.context or [],
            "debug": payload.debug,
        }
    )
    data = {
        "response": result.get("response", ""),
        "language": result.get("language", "unknown"),
        "source": result.get("source", "none"),
        "faq_score": result.get("faq_score"),
        "fr_score": result.get("fr_score"),
        "mg_score": result.get("mg_score"),
        "documents": result.get("rag_docs", []),
        "context_used": payload.context or [],
        "metadata": payload.metadata or {},
    }
    if payload.debug:
        data["trace"] = result.get("trace", [])
    return data


@app.post("/rag/faqs")
async def ingest_faqs(payload: FaqIngestRequest):
    store = get_store()
    result = store.upsert_faqs(
        language=payload.language,
        faqs=[item.model_dump() for item in payload.faqs],
        replace=payload.replace,
    )
    return {"status": "ok", "result": result}


@app.post("/rag/faqs/pairs")
async def ingest_faq_pairs(payload: FaqPairsIngestRequest):
    store = get_store()
    result = store.upsert_faq_pairs(
        pairs=[item.model_dump() for item in payload.faqs],
        replace=payload.replace,
    )
    return {"status": "ok", "result": result}


@app.post("/rag/pdfs")
async def ingest_pdfs(
    files: list[UploadFile] = File(...),
    language: Literal["fr", "mg"] | None = None,
):
    if not files:
        raise HTTPException(status_code=400, detail="No PDF file provided")

    data: list[tuple[str, bytes]] = []
    for upload in files:
        if not upload.filename or not upload.filename.lower().endswith(".pdf"):
            raise HTTPException(status_code=400, detail="Only PDF files are accepted")
        data.append((upload.filename, await upload.read()))

    store = get_store()
    result = store.ingest_pdfs(data, preferred_language=language)
    return {"status": "ok", "result": result}


@app.get("/rag/status")
async def rag_status():
    return {"status": "ok", "result": get_store().status()}


@app.post("/rag/page-routes")
async def ingest_page_routes():
    store = get_store()
    result = store.ingest_page_routes()
    return {"status": "ok", "result": result}


@app.post("/rag/bootstrap/manual-faqs")
async def bootstrap_manual_faqs(replace: bool = False):
    store = get_store()
    result = store.bootstrap_manual_faq_pairs(replace=replace)
    return {"status": "ok", "result": result}
