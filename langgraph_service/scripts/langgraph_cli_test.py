#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.request
from typing import Any


def request_json(method: str, url: str, payload: dict[str, Any] | None = None) -> tuple[int, Any]:
    data = None
    headers = {"Content-Type": "application/json"}
    if payload is not None:
        data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(url=url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            body = resp.read().decode("utf-8")
            return resp.status, json.loads(body) if body else {}
    except urllib.error.URLError as e:
        reason = getattr(e, "reason", str(e))
        return 0, {
            "error": "connection_error",
            "message": f"Impossible de se connecter à {url}",
            "reason": str(reason),
        }
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8")
        try:
            return e.code, json.loads(body)
        except json.JSONDecodeError:
            return e.code, {"error": body}


def print_result(title: str, status: int, payload: Any) -> None:
    print(f"\n=== {title} [{status}] ===")
    print(json.dumps(payload, ensure_ascii=False, indent=2))


def cmd_health(base: str) -> int:
    status, data = request_json("GET", f"{base}/health")
    print_result("HEALTH", status, data)
    return 0 if status < 400 else 1


def cmd_status(base: str) -> int:
    status, data = request_json("GET", f"{base}/rag/status")
    print_result("RAG STATUS", status, data)
    return 0 if status < 400 else 1


def cmd_mermaid(base: str) -> int:
    status, data = request_json("GET", f"{base}/graph/mermaid")
    print_result("GRAPH MERMAID", status, data)
    return 0 if status < 400 else 1


def cmd_query(base: str, text: str, top_k: int, context: list[str], debug: bool) -> int:
    status, data = request_json(
        "POST",
        f"{base}/query",
        {
            "text": text,
            "top_k": top_k,
            "context": context,
            "debug": debug,
        },
    )
    print_result("QUERY", status, data)

    if debug and isinstance(data, dict) and "trace" in data:
        print("\n--- VERBOSE TRACE ---")
        for i, step in enumerate(data.get("trace", []), 1):
            print(f"{i}. {json.dumps(step, ensure_ascii=False)}")
    return 0 if status < 400 else 1


def cmd_add_faq(base: str, language: str, question: str, answer: str, faq_id: str | None) -> int:
    item: dict[str, Any] = {"question": question, "answer": answer}
    if faq_id:
        item["id"] = faq_id
    status, data = request_json(
        "POST",
        f"{base}/rag/faqs",
        {"language": language, "replace": False, "faqs": [item]},
    )
    print_result("ADD FAQ", status, data)
    return 0 if status < 400 else 1


def cmd_add_faq_pair(
    base: str,
    q_fr: str,
    a_fr: str,
    q_mg: str,
    a_mg: str,
    faq_id: str | None,
) -> int:
    item: dict[str, Any] = {
        "question_fr": q_fr,
        "answer_fr": a_fr,
        "question_mg": q_mg,
        "answer_mg": a_mg,
    }
    if faq_id:
        item["id"] = faq_id
    status, data = request_json(
        "POST",
        f"{base}/rag/faqs/pairs",
        {"replace": False, "faqs": [item]},
    )
    print_result("ADD FAQ PAIR", status, data)
    return 0 if status < 400 else 1


def cmd_ingest_routes(base: str, replace: bool) -> int:
    payload = {"replace": True} if replace else {}
    status, data = request_json("POST", f"{base}/rag/page-routes", payload)
    print_result("INGEST PAGE ROUTES", status, data)
    return 0 if status < 400 else 1


def cmd_bootstrap_manual_faqs(base: str, replace: bool) -> int:
    suffix = "?replace=true" if replace else ""
    status, data = request_json("POST", f"{base}/rag/bootstrap/manual-faqs{suffix}", {})
    print_result("BOOTSTRAP MANUAL FAQS", status, data)
    return 0 if status < 400 else 1


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="LangGraph API test CLI")
    parser.add_argument("--base-url", default=os.getenv("LANGGRAPH_BASE_URL", "http://127.0.0.1:8001"))

    sub = parser.add_subparsers(dest="cmd", required=True)
    sub.add_parser("health")
    sub.add_parser("status")
    sub.add_parser("mermaid")
    p_routes = sub.add_parser("ingest-routes")
    p_routes.add_argument("--replace", action="store_true")
    p_boot = sub.add_parser("bootstrap-manual-faqs")
    p_boot.add_argument("--replace", action="store_true")

    p_query = sub.add_parser("query")
    p_query.add_argument("--text", required=True)
    p_query.add_argument("--top-k", type=int, default=3)
    p_query.add_argument("--context", action="append", default=[])
    p_query.add_argument("--debug", action="store_true")

    p_faq = sub.add_parser("add-faq")
    p_faq.add_argument("--language", required=True, choices=["fr", "mg"])
    p_faq.add_argument("--question", required=True)
    p_faq.add_argument("--answer", required=True)
    p_faq.add_argument("--id", default=None)

    p_pair = sub.add_parser("add-faq-pair")
    p_pair.add_argument("--question-fr", required=True)
    p_pair.add_argument("--answer-fr", required=True)
    p_pair.add_argument("--question-mg", required=True)
    p_pair.add_argument("--answer-mg", required=True)
    p_pair.add_argument("--id", default=None)
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    base = args.base_url.rstrip("/")
    if args.cmd == "health":
        return cmd_health(base)
    if args.cmd == "status":
        return cmd_status(base)
    if args.cmd == "mermaid":
        return cmd_mermaid(base)
    if args.cmd == "ingest-routes":
        return cmd_ingest_routes(base, args.replace)
    if args.cmd == "bootstrap-manual-faqs":
        return cmd_bootstrap_manual_faqs(base, args.replace)
    if args.cmd == "query":
        return cmd_query(base, args.text, args.top_k, args.context, args.debug)
    if args.cmd == "add-faq":
        return cmd_add_faq(base, args.language, args.question, args.answer, args.id)
    if args.cmd == "add-faq-pair":
        return cmd_add_faq_pair(
            base,
            args.question_fr,
            args.answer_fr,
            args.question_mg,
            args.answer_mg,
            args.id,
        )
    print(f"Unknown command: {args.cmd}", file=sys.stderr)
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
