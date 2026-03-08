from __future__ import annotations

import hashlib
import json
import math
import os
import re
import unicodedata
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import chromadb
import psycopg2
import psycopg2.extras
from pypdf import PdfReader

try:
    import fasttext
except ImportError:  # pragma: no cover
    fasttext = None

BASE_DIR = Path(__file__).resolve().parent.parent
DATA_DIR = BASE_DIR / "data"
PDF_UPLOAD_DIR = DATA_DIR / "pdf_uploads"
FAQ_FR_PATH = DATA_DIR / "faqs_fr.json"
FAQ_MG_PATH = DATA_DIR / "faqs_mg.json"
FAQ_PAIRS_PATH = DATA_DIR / "faqs_pairs.json"
PAGE_ROUTES_PATH = BASE_DIR.parent / "ai_service" / "knowledge" / "page_routes.json"
MANUAL_PATH = BASE_DIR.parent / "ai_service" / "knowledge" / "express_sale_manual.md"

CHROMA_DIR = DATA_DIR / "chroma"
CHROMA_COLLECTION = "express_sale_rag"

FASTTEXT_MODEL_PATH = DATA_DIR / "lid.176.bin"
FASTTEXT_MODEL_FTZ_PATH = DATA_DIR / "lid.176.ftz"
_FASTTEXT_MODEL: Any | None = None

FAQ_MATCH_THRESHOLD = 0.52
FAQ_SHORT_QUERY_THRESHOLD = 0.55
FAQ_TOPIC_THRESHOLD = 0.38
FAQ_NON_TOPIC_THRESHOLD = 0.62
FAQ_MIN_OVERLAP = 1
RAG_MATCH_THRESHOLD = 0.18
DB_DEFAULT_LIMIT = 20
DB_MAX_LIMIT = 50


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def _tokenize(text: str) -> list[str]:
    return re.findall(r"[a-zA-ZÀ-ÿ']+", text.lower())


def _normalize_token(token: str) -> str:
    raw = token.lower().strip()
    raw = TOKEN_NORMALIZATION.get(raw, raw)
    raw = "".join(c for c in unicodedata.normalize("NFD", raw) if unicodedata.category(c) != "Mn")
    if raw.endswith("s") and len(raw) > 4:
        raw = raw[:-1]
    return raw


def _normalized_tokens(text: str) -> set[str]:
    out: set[str] = set()
    for t in _tokenize(text):
        nt = _normalize_token(t)
        if not nt:
            continue
        if nt in FR_STOPWORDS or nt in MG_STOPWORDS:
            continue
        if len(nt) <= 1:
            continue
        out.add(nt)
    return out


def _detect_topics(tokens: set[str]) -> set[str]:
    found: set[str] = set()
    for topic, keys in INTENT_TOPICS.items():
        norm_keys = {_normalize_token(k) for k in keys}
        if tokens & norm_keys:
            found.add(topic)
    return found


def has_route_intent(text: str) -> bool:
    tokens = _normalized_tokens(text)
    return any(t in ROUTE_INTENT_MARKERS for t in tokens)


def _vectorize(text: str) -> dict[str, float]:
    tokens = _tokenize(text)
    vec: dict[str, float] = {}
    for token in tokens:
        vec[token] = vec.get(token, 0.0) + 1.0
    return vec


def _cosine_similarity(a: dict[str, float], b: dict[str, float]) -> float:
    if not a or not b:
        return 0.0
    common = set(a.keys()) & set(b.keys())
    dot = sum(a[t] * b[t] for t in common)
    norm_a = math.sqrt(sum(v * v for v in a.values()))
    norm_b = math.sqrt(sum(v * v for v in b.values()))
    if norm_a == 0.0 or norm_b == 0.0:
        return 0.0
    return dot / (norm_a * norm_b)


def _split_text(text: str, chunk_size: int = 1200, overlap: int = 150) -> list[str]:
    text = re.sub(r"\s+", " ", text).strip()
    if not text:
        return []
    chunks: list[str] = []
    start = 0
    while start < len(text):
        end = min(start + chunk_size, len(text))
        chunks.append(text[start:end])
        if end == len(text):
            break
        start = max(end - overlap, 0)
    return chunks


FR_MARKERS = {
    "bonjour",
    "salut",
    "merci",
    "comment",
    "pourquoi",
    "quand",
    "vente",
    "produit",
    "stock",
    "réservation",
    "facture",
    "paiement",
}

MG_MARKERS = {
    "manao",
    "ahoana",
    "misaotra",
    "inona",
    "oviana",
    "fivarotana",
    "entana",
    "tahiry",
    "fandoavana",
    "famandrihana",
    "azafady",
}

ROUTE_INTENT_MARKERS = {
    "page",
    "route",
    "redirige",
    "redirection",
    "aller",
    "ouvre",
    "ouvrir",
    "naviguer",
    "menu",
    "dashboard",
    "statistique",
    "statistiques",
    "parametres",
    "settings",
    "aiza",
    "pejy",
}

INTENT_TOPICS = {
    "reservation": {"reservation", "reservations", "reserver", "acompte", "completer", "expir", "reservtation"},
    "reapprovisionnement": {"reapprovisionnement", "reception", "arrive", "transitaire", "fournisseur", "commande"},
    "transitaire": {"transitaire", "transitaires", "freight", "forwarder"},
    "lot_batch": {"lot", "lots", "batch", "batches", "fifo"},
    "evaluation_reception": {"noter", "note", "notation", "evaluation", "qualite", "reception"},
    "credit": {"credit", "echeance", "echeancier", "impaye"},
    "vente": {"vente", "ventes", "panier", "point", "paiement"},
    "produit": {"produit", "produits", "variante", "stock", "attribut"},
    "dashboard": {"dashboard", "statistique", "statistiques", "kpi", "financieres"},
}

TOKEN_NORMALIZATION = {
    "reservtation": "reservation",
    "réservtation": "reservation",
    "statisique": "statistique",
    "réapprovionnement": "reapprovisionnement",
    "usuivre": "suivre",
    "battch": "batch",
    "batche": "batch",
    "lisitrin": "lisitra",
    "client rehetra": "clients",
}

FR_STOPWORDS = {
    "le", "la", "les", "de", "des", "du", "un", "une", "ce", "cet", "cette", "ces",
    "qu", "que", "qui", "quoi", "est", "et", "ou", "où", "dans", "sur", "pour",
    "par", "avec", "sans", "je", "tu", "il", "elle", "nous", "vous", "ils", "elles",
    "comment", "peux", "nouvelle", "nouveau", "moi", "vers", "page", "route",
}

MG_STOPWORDS = {
    "ny", "sy", "dia", "ao", "amin", "aminny", "amin'ny", "ho", "an", "izay",
    "izaho", "ianao", "izy", "isika", "ianareo", "ireo", "ve", "raha", "eto",
    "any", "izao", "ity", "izany",
}

DB_ENTITY_MAP = {
    "customers": {
        "aliases": {"client", "clients", "customer", "customers"},
        "description_fr": "Liste des clients (nom, téléphone, statut).",
        "description_mg": "Lisitrin'ny clients (anarana, telefaonina, sata).",
        "list_sql": "SELECT id, name, phone, is_active FROM customers ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM customers",
        "order_col": "id",
    },
    "freight_forwarders": {
        "aliases": {"transitaire", "transitaires", "forwarder", "freight"},
        "description_fr": "Liste des transitaires (nom, contact, statut).",
        "description_mg": "Lisitrin'ny transitaires (anarana, contact, sata).",
        "list_sql": "SELECT id, name, contact, is_active FROM freight_forwarders ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM freight_forwarders",
        "order_col": "id",
    },
    "suppliers": {
        "aliases": {"fournisseur", "fournisseurs", "supplier", "suppliers"},
        "description_fr": "Liste des fournisseurs (nom, contact, statut).",
        "description_mg": "Lisitrin'ny fournisseurs (anarana, contact, sata).",
        "list_sql": "SELECT id, name, contact, is_active FROM suppliers ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM suppliers",
        "order_col": "id",
    },
    "products": {
        "aliases": {"produit", "produits", "product", "products"},
        "description_fr": "Liste des produits (nom, prix, statut).",
        "description_mg": "Lisitrin'ny produits (anarana, vidy, sata).",
        "list_sql": "SELECT id, name, base_price, is_active FROM products ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM products",
        "order_col": "id",
    },
    "sales": {
        "aliases": {"vente", "ventes", "sales", "sale"},
        "description_fr": "Liste des ventes (numéro, date, total, statut).",
        "description_mg": "Lisitrin'ny ventes (laharana, daty, total, sata).",
        "list_sql": "SELECT id, sale_number, sale_date, total_amount, payment_status FROM sales ORDER BY sale_date DESC NULLS LAST LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM sales",
        "order_col": "sale_date",
    },
    "sales_immediate": {
        "aliases": {"vente rapide", "ventes rapides", "vente immediate", "ventes immediates", "vente immédiate", "ventes immédiates"},
        "description_fr": "Liste des ventes immédiates (numéro, date, total, statut).",
        "description_mg": "Lisitrin'ny ventes immédiates (laharana, daty, total, sata).",
        "list_sql": "SELECT id, sale_number, sale_date, total_amount, payment_status FROM sales WHERE sale_type = 'immediate' ORDER BY sale_date DESC NULLS LAST LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM sales WHERE sale_type = 'immediate'",
        "order_col": "sale_date",
    },
    "reservations": {
        "aliases": {"reservation", "reservations", "réservation", "réservations"},
        "description_fr": "Liste des réservations (statut, total, date d'expiration).",
        "description_mg": "Lisitrin'ny reservations (sata, total, daty fivaranany).",
        "list_sql": "SELECT id, sale_id, customer_id, status, total_amount, expiry_date FROM reservations ORDER BY expiry_date DESC NULLS LAST LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM reservations",
        "order_col": "expiry_date",
    },
    "credits": {
        "aliases": {"credit", "crédit", "credits", "crédits"},
        "description_fr": "Liste des crédits (statut, montant dû).",
        "description_mg": "Lisitrin'ny crédits (sata, vola mbola tsy voaloa).",
        "list_sql": "SELECT id, sale_id, customer_id, status, amount_due FROM credits ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM credits",
        "order_col": "id",
    },
    "stock_receipts": {
        "aliases": {"reapprovisionnement", "réapprovisionnement", "reception", "réception", "receptions", "réceptions"},
        "description_fr": "Liste des réceptions (statut, coût, date).",
        "description_mg": "Lisitrin'ny réceptions (sata, coût, daty).",
        "list_sql": "SELECT id, receipt_number, status, total_cost_ariary, expected_delivery_date FROM stock_receipts ORDER BY id DESC LIMIT %s",
        "count_sql": "SELECT count(*) AS total FROM stock_receipts",
        "order_col": "id",
    },
}

DB_INTENT_MARKERS = {
    "liste", "lisitra", "list", "voir", "affiche", "afficher",
    "combien", "nombre", "total", "count",
    "dernier", "recents", "récent", "recent", "derniers", "nouvelles",
    "schema", "structure", "colonnes", "colonne", "table",
}

DEFINITION_MARKERS = {
    "c", "est", "quoi", "qu", "definir", "definition",
    "inona", "iza",
}


def _db_config() -> dict[str, Any]:
    def _from_env_file() -> dict[str, str]:
        env_path = BASE_DIR.parent / "backend" / ".env"
        values: dict[str, str] = {}
        if not env_path.exists():
            return values
        for line in env_path.read_text(encoding="utf-8").splitlines():
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, val = line.split("=", 1)
            key = key.strip()
            val = val.strip().strip('"').strip("'")
            values[key] = val
        return values

    file_env = _from_env_file()
    return {
        "host": os.getenv("DB_HOST", file_env.get("DB_HOST", "127.0.0.1")),
        "port": int(os.getenv("DB_PORT", file_env.get("DB_PORT", "5432"))),
        "dbname": os.getenv("DB_DATABASE", file_env.get("DB_DATABASE", "express_sale")),
        "user": os.getenv("DB_USERNAME", file_env.get("DB_USERNAME", "postgres")),
        "password": os.getenv("DB_PASSWORD", file_env.get("DB_PASSWORD", "")),
    }


def _db_connect():
    cfg = _db_config()
    return psycopg2.connect(
        host=cfg["host"],
        port=cfg["port"],
        dbname=cfg["dbname"],
        user=cfg["user"],
        password=cfg["password"],
    )


def _detect_db_intent(text: str) -> tuple[str, str] | None:
    tokens = _normalized_tokens(text)
    if not tokens or not (tokens & DB_INTENT_MARKERS):
        # allow short queries like "et crédits?" or "clients?"
        for table, cfg in DB_ENTITY_MAP.items():
            if _match_alias(tokens, cfg["aliases"]):
                if len(tokens) <= 3:
                    # If the user explicitly asked for a list, honor it.
                    if tokens & {"liste", "lisitra", "list", "lister", "afficher", "affiche"}:
                        return table, "list"
                    return table, "count"
        return None

    if _is_definition_question(text):
        return None

    action = "list"
    if any(t in tokens for t in {"combien", "nombre", "total", "count"}):
        action = "count"
    elif any(t in tokens for t in {"schema", "structure", "colonnes", "colonne", "table"}):
        action = "schema"
    elif any(t in tokens for t in {"dernier", "recents", "récent", "recent", "derniers", "nouvelles"}):
        action = "recent"

    for table, cfg in DB_ENTITY_MAP.items():
        if _match_alias(tokens, cfg["aliases"]):
            return table, action
    return None


def _detect_db_intent_from_context(text: str, history: list[str]) -> tuple[str, str] | None:
    tokens = _normalized_tokens(text)
    if not tokens:
        return None
    if _is_definition_question(text):
        return None

    table = None
    for t, cfg in DB_ENTITY_MAP.items():
        if _match_alias(tokens, cfg["aliases"]):
            table = t
            break
    if not table:
        return None

    if tokens & DB_INTENT_MARKERS:
        return _detect_db_intent(text)

    hist = "\n".join(history[-4:]).lower()
    if "total:" in hist or "total" in hist or "isan'ny total" in hist:
        return table, "count"
    if "liste des" in hist or "lisitrin" in hist or "résultats base de données" in hist:
        return table, "list"
    if "schéma de la table" in hist or "firafitry ny table" in hist:
        return table, "schema"
    return None


def _run_db_query(table: str, action: str, limit: int = DB_DEFAULT_LIMIT) -> dict[str, Any] | None:
    cfg = DB_ENTITY_MAP.get(table)
    if not cfg:
        return None
    safe_limit = max(1, min(int(limit), DB_MAX_LIMIT))
    if action == "schema":
        sql = """
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = %s
            ORDER BY ordinal_position
        """
        params = (table,)
    elif action == "count":
        sql = cfg["count_sql"]
        params = ()
    elif action == "recent":
        order_col = cfg.get("order_col", "id")
        sql = f"SELECT * FROM {table} ORDER BY {order_col} DESC NULLS LAST LIMIT %s"
        params = (safe_limit,)
    else:
        sql = cfg["list_sql"]
        params = (safe_limit,)

    with _db_connect() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()
    return {
        "table": table,
        "action": action,
        "rows": rows,
        "description_fr": cfg.get("description_fr", ""),
        "description_mg": cfg.get("description_mg", ""),
    }


def _score_language(tokens: list[str], markers: set[str]) -> float:
    if not tokens:
        return 0.0
    hits = sum(1 for t in tokens if t in markers)
    return hits / max(len(tokens), 1)


def _load_fasttext_model() -> Any | None:
    global _FASTTEXT_MODEL
    if _FASTTEXT_MODEL is not None:
        return _FASTTEXT_MODEL
    if fasttext is None:
        return None
    try:
        if FASTTEXT_MODEL_PATH.exists():
            _FASTTEXT_MODEL = fasttext.load_model(str(FASTTEXT_MODEL_PATH))
        elif FASTTEXT_MODEL_FTZ_PATH.exists():
            _FASTTEXT_MODEL = fasttext.load_model(str(FASTTEXT_MODEL_FTZ_PATH))
        else:
            _FASTTEXT_MODEL = None
    except Exception:
        return None
    return _FASTTEXT_MODEL


def _detect_with_fasttext(text: str) -> tuple[str, float, float] | None:
    model = _load_fasttext_model()
    if model is None:
        return None
    sample = re.sub(r"\s+", " ", text).strip()
    if not sample:
        return "unknown", 0.0, 0.0
    try:
        labels, probs = model.predict(sample, k=5)
    except Exception:
        return None

    score_map = {
        label.replace("__label__", ""): float(prob)
        for label, prob in zip(labels, probs)
    }
    fr_score = score_map.get("fr", 0.0)
    mg_score = score_map.get("mg", 0.0)
    top = max(fr_score, mg_score)
    margin = abs(fr_score - mg_score)

    if top < 0.40:
        return "unknown", fr_score, mg_score
    if min(fr_score, mg_score) >= 0.25 and margin < 0.18:
        return "mixed", fr_score, mg_score
    return ("fr", fr_score, mg_score) if fr_score > mg_score else ("mg", fr_score, mg_score)


def detect_language_with_models(text: str) -> tuple[str, float, float]:
    fasttext_result = _detect_with_fasttext(text)
    if fasttext_result is not None:
        lang, fr_score, mg_score = fasttext_result
        # Near-threshold Malagasy queries are common in short mixed phrases.
        if lang == "unknown" and mg_score >= 0.38 and fr_score <= 0.12:
            return "mg", fr_score, mg_score
        return lang, fr_score, mg_score

    tokens = _tokenize(text)
    fr_score = _score_language(tokens, FR_MARKERS)
    mg_score = _score_language(tokens, MG_MARKERS)
    if max(fr_score, mg_score) < 0.03:
        return "unknown", fr_score, mg_score
    if abs(fr_score - mg_score) < 0.015:
        return "mixed", fr_score, mg_score
    return ("fr", fr_score, mg_score) if fr_score > mg_score else ("mg", fr_score, mg_score)


class HashEmbeddingFunction:
    def __init__(self, dim: int = 512) -> None:
        self.dim = dim

    def __call__(self, input: list[str]) -> list[list[float]]:
        vectors: list[list[float]] = []
        for text in input:
            vec = [0.0] * self.dim
            for token in _tokenize(text):
                digest = hashlib.md5(token.encode("utf-8")).hexdigest()
                idx = int(digest, 16) % self.dim
                vec[idx] += 1.0
            norm = math.sqrt(sum(v * v for v in vec))
            if norm > 0.0:
                vec = [v / norm for v in vec]
            vectors.append(vec)
        return vectors


class RagStore:
    def __init__(self) -> None:
        DATA_DIR.mkdir(parents=True, exist_ok=True)
        PDF_UPLOAD_DIR.mkdir(parents=True, exist_ok=True)
        CHROMA_DIR.mkdir(parents=True, exist_ok=True)
        self._bootstrap_files()
        self.client = chromadb.PersistentClient(path=str(CHROMA_DIR))
        self.collection = self.client.get_or_create_collection(
            name=CHROMA_COLLECTION,
            metadata={"hnsw:space": "cosine"},
            embedding_function=HashEmbeddingFunction(),
        )
        self.reload()

    def _bootstrap_files(self) -> None:
        for path in (FAQ_FR_PATH, FAQ_MG_PATH, FAQ_PAIRS_PATH):
            if not path.exists():
                path.write_text("[]", encoding="utf-8")

    def reload(self) -> None:
        self.faq_pairs = self._load_json(FAQ_PAIRS_PATH)
        self.faqs = {
            "fr": self._load_json(FAQ_FR_PATH),
            "mg": self._load_json(FAQ_MG_PATH),
        }
        # If pairs exist, treat them as source of truth and expose language views.
        if self.faq_pairs:
            self.faqs["fr"] = [
                {
                    "id": p["id"],
                    "question": p.get("question_fr", ""),
                    "answer": p.get("answer_fr", ""),
                    "updated_at": p.get("updated_at"),
                }
                for p in self.faq_pairs
                if p.get("question_fr") and p.get("answer_fr")
            ]
            self.faqs["mg"] = [
                {
                    "id": p["id"],
                    "question": p.get("question_mg", ""),
                    "answer": p.get("answer_mg", ""),
                    "updated_at": p.get("updated_at"),
                }
                for p in self.faq_pairs
                if p.get("question_mg") and p.get("answer_mg")
            ]

    def _load_json(self, path: Path) -> list[dict[str, Any]]:
        try:
            data = json.loads(path.read_text(encoding="utf-8"))
        except (FileNotFoundError, json.JSONDecodeError):
            return []
        if isinstance(data, list):
            return [d for d in data if isinstance(d, dict)]
        return []

    def _save_json(self, path: Path, payload: list[dict[str, Any]]) -> None:
        path.write_text(
            json.dumps(payload, ensure_ascii=False, indent=2),
            encoding="utf-8",
        )

    def _delete_by_filter(self, where: dict[str, Any]) -> None:
        data = self.collection.get(where=where, include=[])
        ids = data.get("ids", []) if isinstance(data, dict) else []
        if ids:
            self.collection.delete(ids=ids)

    @staticmethod
    def _distance_to_score(distance: float) -> float:
        return max(0.0, min(1.0, 1.0 - float(distance)))

    def _reindex_faq_pairs_in_chroma(self) -> None:
        self._delete_by_filter({"source": "faq"})
        ids: list[str] = []
        docs: list[str] = []
        metas: list[dict[str, Any]] = []

        for pair in self.faq_pairs:
            pair_id = str(pair["id"])
            q_fr = str(pair.get("question_fr", "")).strip()
            a_fr = str(pair.get("answer_fr", "")).strip()
            q_mg = str(pair.get("question_mg", "")).strip()
            a_mg = str(pair.get("answer_mg", "")).strip()
            updated_at = pair.get("updated_at") or _now_iso()

            if q_fr and a_fr:
                ids.append(f"faq:fr:{pair_id}")
                docs.append(f"Q: {q_fr}\nA: {a_fr}")
                metas.append(
                    {
                        "source": "faq",
                        "language": "fr",
                        "pair_id": pair_id,
                        "question": q_fr,
                        "answer": a_fr,
                        "updated_at": updated_at,
                    }
                )
            if q_mg and a_mg:
                ids.append(f"faq:mg:{pair_id}")
                docs.append(f"Q: {q_mg}\nA: {a_mg}")
                metas.append(
                    {
                        "source": "faq",
                        "language": "mg",
                        "pair_id": pair_id,
                        "question": q_mg,
                        "answer": a_mg,
                        "updated_at": updated_at,
                    }
                )

        if ids:
            self.collection.upsert(ids=ids, documents=docs, metadatas=metas)

    def upsert_faq_pairs(self, pairs: list[dict[str, Any]], replace: bool = False) -> dict[str, Any]:
        cleaned: list[dict[str, Any]] = []
        for idx, item in enumerate(pairs):
            q_fr = str(item.get("question_fr", "")).strip()
            a_fr = str(item.get("answer_fr", "")).strip()
            q_mg = str(item.get("question_mg", "")).strip()
            a_mg = str(item.get("answer_mg", "")).strip()
            if not (q_fr and a_fr and q_mg and a_mg):
                continue
            pair_id = str(item.get("id") or f"faq_pair_{idx+1}_{int(datetime.now().timestamp())}")
            cleaned.append(
                {
                    "id": pair_id,
                    "question_fr": q_fr,
                    "answer_fr": a_fr,
                    "question_mg": q_mg,
                    "answer_mg": a_mg,
                    "tags": item.get("tags", []),
                    "updated_at": _now_iso(),
                }
            )

        if replace:
            self.faq_pairs = cleaned
        else:
            by_id = {str(p["id"]): p for p in self.faq_pairs}
            for item in cleaned:
                by_id[str(item["id"])] = item
            self.faq_pairs = list(by_id.values())

        self._save_json(FAQ_PAIRS_PATH, self.faq_pairs)
        self._save_json(
            FAQ_FR_PATH,
            [
                {"id": p["id"], "question": p["question_fr"], "answer": p["answer_fr"], "updated_at": p["updated_at"]}
                for p in self.faq_pairs
            ],
        )
        self._save_json(
            FAQ_MG_PATH,
            [
                {"id": p["id"], "question": p["question_mg"], "answer": p["answer_mg"], "updated_at": p["updated_at"]}
                for p in self.faq_pairs
            ],
        )
        self._reindex_faq_pairs_in_chroma()
        self.reload()
        return {
            "faq_pairs": len(self.faq_pairs),
            "faq_count": {"fr": len(self.faqs["fr"]), "mg": len(self.faqs["mg"])},
        }

    def upsert_faqs(self, language: str, faqs: list[dict[str, Any]], replace: bool = False) -> dict[str, Any]:
        language = language.lower()
        if language not in {"fr", "mg"}:
            raise ValueError("language must be fr or mg")

        cleaned: list[dict[str, Any]] = []
        for item in faqs:
            question = str(item.get("question", "")).strip()
            answer = str(item.get("answer", "")).strip()
            if not question or not answer:
                continue
            cleaned.append(
                {
                    "id": str(item.get("id") or f"faq_{language}_{len(cleaned)+1}_{int(datetime.now().timestamp())}"),
                    "question": question,
                    "answer": answer,
                    "tags": item.get("tags", []),
                    "updated_at": _now_iso(),
                }
            )

        # Backward compatibility path: convert monolingual entries into pairs.
        existing = {str(p["id"]): p for p in self.faq_pairs}
        if replace:
            # Replace only the requested language side, preserve opposite side when IDs match.
            for mono in cleaned:
                pid = str(mono["id"])
                current = existing.get(pid, {"id": pid})
                if language == "fr":
                    current["question_fr"] = mono["question"]
                    current["answer_fr"] = mono["answer"]
                    current.setdefault("question_mg", current.get("question_mg", mono["question"]))
                    current.setdefault("answer_mg", current.get("answer_mg", mono["answer"]))
                else:
                    current["question_mg"] = mono["question"]
                    current["answer_mg"] = mono["answer"]
                    current.setdefault("question_fr", current.get("question_fr", mono["question"]))
                    current.setdefault("answer_fr", current.get("answer_fr", mono["answer"]))
                current["updated_at"] = _now_iso()
                current.setdefault("tags", [])
                existing[pid] = current
        else:
            for mono in cleaned:
                pid = str(mono["id"])
                current = existing.get(pid, {"id": pid})
                if language == "fr":
                    current["question_fr"] = mono["question"]
                    current["answer_fr"] = mono["answer"]
                    current.setdefault("question_mg", mono["question"])
                    current.setdefault("answer_mg", mono["answer"])
                else:
                    current["question_mg"] = mono["question"]
                    current["answer_mg"] = mono["answer"]
                    current.setdefault("question_fr", mono["question"])
                    current.setdefault("answer_fr", mono["answer"])
                current["updated_at"] = _now_iso()
                current.setdefault("tags", [])
                existing[pid] = current

        self.faq_pairs = list(existing.values())
        self._save_json(FAQ_PAIRS_PATH, self.faq_pairs)
        self._save_json(
            FAQ_FR_PATH,
            [
                {"id": p["id"], "question": p.get("question_fr", ""), "answer": p.get("answer_fr", ""), "updated_at": p.get("updated_at")}
                for p in self.faq_pairs
                if p.get("question_fr") and p.get("answer_fr")
            ],
        )
        self._save_json(
            FAQ_MG_PATH,
            [
                {"id": p["id"], "question": p.get("question_mg", ""), "answer": p.get("answer_mg", ""), "updated_at": p.get("updated_at")}
                for p in self.faq_pairs
                if p.get("question_mg") and p.get("answer_mg")
            ],
        )
        self._reindex_faq_pairs_in_chroma()
        self.reload()
        return {"language": language, "faq_count": len(self.faqs[language]), "faq_pairs": len(self.faq_pairs)}

    def ingest_pdfs(self, files: list[tuple[str, bytes]], preferred_language: str | None = None) -> dict[str, Any]:
        added_chunks = 0
        file_summaries: list[dict[str, Any]] = []

        for filename, payload in files:
            safe_name = re.sub(r"[^a-zA-Z0-9_.-]", "_", filename)
            local_path = PDF_UPLOAD_DIR / safe_name
            local_path.write_bytes(payload)

            reader = PdfReader(str(local_path))
            pages = [page.extract_text() or "" for page in reader.pages]
            full_text = "\n".join(pages).strip()
            if not full_text:
                file_summaries.append({"filename": filename, "chunks": 0})
                continue

            lang = preferred_language
            if lang not in {"fr", "mg"}:
                lang, _, _ = detect_language_with_models(full_text[:3000])
                if lang not in {"fr", "mg"}:
                    lang = "fr"

            self._delete_by_filter({"$and": [{"source": "pdf"}, {"filename": filename}]})

            chunks = _split_text(full_text)
            if chunks:
                ids = [f"pdf:{safe_name}:{idx}" for idx in range(len(chunks))]
                metas = [
                    {
                        "source": "pdf",
                        "language": lang,
                        "filename": filename,
                        "chunk_index": idx,
                        "updated_at": _now_iso(),
                    }
                    for idx in range(len(chunks))
                ]
                self.collection.add(ids=ids, documents=chunks, metadatas=metas)

            added_chunks += len(chunks)
            file_summaries.append({"filename": filename, "chunks": len(chunks), "language": lang})

        self.reload()
        return {"files": file_summaries, "chunks_added": added_chunks}

    def ingest_page_routes(self, replace: bool = True) -> dict[str, Any]:
        if not PAGE_ROUTES_PATH.exists():
            return {"ingested": 0, "message": f"missing file: {PAGE_ROUTES_PATH}"}

        try:
            payload = json.loads(PAGE_ROUTES_PATH.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            return {"ingested": 0, "message": "invalid JSON in page_routes.json"}

        pages = payload.get("pages", []) if isinstance(payload, dict) else []
        if not isinstance(pages, list):
            return {"ingested": 0, "message": "invalid pages format"}

        if replace:
            self._delete_by_filter({"source": "page_route"})

        ids: list[str] = []
        docs: list[str] = []
        metas: list[dict[str, Any]] = []
        total_chunks = 0

        for idx, page in enumerate(pages):
            if not isinstance(page, dict):
                continue
            path = str(page.get("path", "")).strip()
            name = str(page.get("name", "")).strip()
            description = str(page.get("description", "")).strip()
            if not path and not name and not description:
                continue

            highlights = ", ".join(page.get("highlights", []) or [])
            actions = ", ".join(page.get("actions", []) or [])
            roles = ", ".join(page.get("roles", []) or [])
            keywords = ", ".join(page.get("keywords", []) or [])
            aliases = ", ".join(page.get("aliases", []) or [])
            content = (
                f"La page '{name}' utilise la route '{path}'. "
                f"Elle sert à: {description}. "
                f"Fonctions principales: {highlights if highlights else 'navigation et gestion'}. "
                f"Actions possibles: {actions if actions else 'consulter'}. "
                f"Rôles autorisés: {roles if roles else 'public'}. "
                f"Mots-clés: {keywords}. "
                f"Alias: {aliases if aliases else 'aucun'}."
            ).strip()

            lang, _, _ = detect_language_with_models(content)
            if lang not in {"fr", "mg", "mixed"}:
                lang = "fr"

            chunks = _split_text(content, chunk_size=800, overlap=100) or [content]
            for c_idx, chunk in enumerate(chunks):
                ids.append(f"route:{idx}:{c_idx}:{hashlib.md5((path+name+str(c_idx)).encode('utf-8')).hexdigest()[:12]}")
                docs.append(chunk)
                metas.append(
                    {
                        "source": "page_route",
                        "language": lang,
                        "page_path": path,
                        "page_name": name,
                        "chunk_index": c_idx,
                        "updated_at": _now_iso(),
                    }
                )
                total_chunks += 1

        if ids:
            self.collection.add(ids=ids, documents=docs, metadatas=metas)

        return {"ingested": len(ids), "pages": len(pages), "chunks": total_chunks}

    def bootstrap_manual_faq_pairs(self, replace: bool = False) -> dict[str, Any]:
        if not MANUAL_PATH.exists():
            return {"status": "error", "message": f"missing file: {MANUAL_PATH}"}

        pairs = [
            {
                "id": "manual_001",
                "question_fr": "Comment créer un nouveau produit dans Express Sale ?",
                "answer_fr": "Allez dans Produits, cliquez sur 'Nouveau produit', renseignez nom et prix de vente estimatif, configurez les attributs puis cliquez sur 'Créer'.",
                "question_mg": "Ahoana no hamoronana vokatra vaovao ao amin'ny Express Sale?",
                "answer_mg": "Mandehana any amin'ny Produits, tsindrio 'Nouveau produit', fenoy ny anarana sy ny vidiny, amboary ny attributs dia tsindrio 'Créer'.",
                "tags": ["produits", "creation"],
            },
            {
                "id": "manual_002",
                "question_fr": "Pourquoi les variantes sont-elles obligatoires ?",
                "answer_fr": "Toutes les opérations (vente, réservation, transfert, réapprovisionnement) se font sur une variante. Un produit sans variante ne peut pas être vendu.",
                "question_mg": "Fa maninona no tsy maintsy misy variante?",
                "answer_mg": "Ny asa rehetra (varotra, famandrihana, transfert, réapprovisionnement) dia atao amin'ny variante. Tsy azo amidy ny produit tsy misy variante.",
                "tags": ["variantes", "stock"],
            },
            {
                "id": "manual_003",
                "question_fr": "Comment créer une variante ?",
                "answer_fr": "Depuis la fiche d'un produit, section Variantes, cliquez sur 'Ajouter une variante', renseignez attributs obligatoires, seuil d'alerte et validez.",
                "question_mg": "Ahoana no hamoronana variante?",
                "answer_mg": "Ao amin'ny fiche produit, any amin'ny Variantes, tsindrio 'Ajouter une variante', fenoy ny attributs obligatoire sy seuil d'alerte dia valider.",
                "tags": ["variantes", "produits"],
            },
            {
                "id": "manual_004",
                "question_fr": "Comment procéder au réapprovisionnement, quelles sont les étapes ?",
                "answer_fr": "Étapes: 1) Réapprovisionnement > Nouvelle Réception. 2) Choisir devise, produits/variantes et quantités. 3) Renseigner fournisseur/transitaire. 4) Choisir le compte de paiement. 5) Valider la réception.",
                "question_mg": "Ahoana no hanaovana réception réapprovisionnement?",
                "answer_mg": "Mandehana any Réapprovisionnement > Nouvelle Réception, fidio devise, produits/variantes sy quantité, avy eo fournisseur/transitaire, farany compte de paiement ary valider.",
                "tags": ["reapprovisionnement", "reception"],
            },
            {
                "id": "manual_005",
                "question_fr": "Que faire quand la commande est arrivée ?",
                "answer_fr": "Marquez la réception 'Arrivé', vérifiez les quantités réellement reçues, puis gérez paiements, évaluation qualité et répartition des coûts.",
                "question_mg": "Inona no atao rehefa tonga ny commande?",
                "answer_mg": "Ataovy status 'Arrivé', jereo ny quantité tena voaray, avy eo ataovy paiements, évaluation qualité, ary répartition des coûts.",
                "tags": ["reapprovisionnement", "arrivee"],
            },
            {
                "id": "manual_006",
                "question_fr": "Quelle méthode de répartition des coûts est recommandée ?",
                "answer_fr": "La méthode par pondération (quantité × prix) est recommandée pour une répartition plus réaliste des coûts.",
                "question_mg": "Inona ny méthode tsara indrindra amin'ny répartition des coûts?",
                "answer_mg": "Ny méthode par pondération (quantité × prix) no soso-kevitra indrindra.",
                "tags": ["couts", "repartition"],
            },
            {
                "id": "manual_007",
                "question_fr": "Comment fonctionne le FIFO des lots (batches) ?",
                "answer_fr": "Express Sale sort d'abord les lots les plus anciens lors des ventes. Chaque réception crée automatiquement des batches.",
                "question_mg": "Ahoana ny fiasan'ny FIFO amin'ny batches?",
                "answer_mg": "Ny lots tranainy indrindra no avoaka voalohany amin'ny varotra. Ny réception tsirairay dia mamorona batch ho azy.",
                "tags": ["fifo", "batches", "stock"],
            },
            {
                "id": "manual_007b",
                "question_fr": "Qu'est-ce qu'un lot (batch) ?",
                "answer_fr": "Un lot (batch) est un groupe d'articles reçus ensemble au même moment. Il sert à tracer le stock et à appliquer le FIFO.",
                "question_mg": "Inona ny lot na batch?",
                "answer_mg": "Ny lot na batch dia andiana entana voaray miaraka amin'ny fotoana iray. Izy io no ampiasaina hanarahana stock sy hampiharana FIFO.",
                "tags": ["lot", "batch", "fifo", "stock"],
            },
            {
                "id": "manual_008",
                "question_fr": "Comment faire une vente rapide ?",
                "answer_fr": "Dans Point de Vente, choisissez 'Vente rapide', sélectionnez client ou anonyme, ajoutez les produits/variantes, puis validez le paiement.",
                "question_mg": "Ahoana no hanaovana vente rapide?",
                "answer_mg": "Ao amin'ny Point de Vente, fidio 'Vente rapide', mifidiana client na anonyme, ampio produits/variantes, dia valider ny paiement.",
                "tags": ["vente", "point_de_vente"],
            },
            {
                "id": "manual_009",
                "question_fr": "Comment annuler correctement une vente rapide ?",
                "answer_fr": "1) Annuler la vente pour restaurer le stock. 2) Annuler la transaction financière si nécessaire (compte suffisant et même utilisateur créateur).",
                "question_mg": "Ahoana no hanafoanana vente rapide tsara?",
                "answer_mg": "1) Foano ny vente hamerenana stock. 2) Foano koa ny transaction financière raha ilaina (solde ampy sy mpampiasa namorona ihany).",
                "tags": ["annulation", "vente"],
            },
            {
                "id": "manual_010",
                "question_fr": "Comment enregistrer ou créer une nouvelle réservation ?",
                "answer_fr": "Point de Vente > Réservation, client obligatoire, choisissez produits (tous emplacements), définissez acompte + date d'expiration + paiement, puis validez l'enregistrement.",
                "question_mg": "Ahoana no hanaovana réservation?",
                "answer_mg": "Point de Vente > Réservation, tsy maintsy misy client, fidio produits (emplacements rehetra), apetraho acompte + date d'expiration + paiement, dia valider.",
                "tags": ["reservation"],
            },
            {
                "id": "manual_011",
                "question_fr": "Comment finaliser une réservation ?",
                "answer_fr": "Ouvrez la réservation, cliquez sur 'Compléter la réservation', encaissez le solde restant avec mode de paiement et compte, puis validez.",
                "question_mg": "Ahoana no hamitana réservation?",
                "answer_mg": "Sokafy ny réservation, tsindrio 'Compléter la réservation', raiso ny solde sisa miaraka amin'ny mode de paiement sy compte, dia valider.",
                "tags": ["reservation", "paiement"],
            },
            {
                "id": "manual_012",
                "question_fr": "Comment créer une vente à crédit ?",
                "answer_fr": "Point de Vente > Crédit, client obligatoire, ajoutez produits puis configurez l'échéancier (dates + montants) et validez.",
                "question_mg": "Ahoana no hanaovana vente à crédit?",
                "answer_mg": "Point de Vente > Crédit, tsy maintsy misy client, ampio produits avy eo amboary échéancier (daty + vola) dia valider.",
                "tags": ["credit", "vente"],
            },
            {
                "id": "manual_013",
                "question_fr": "Comment enregistrer un paiement de crédit ?",
                "answer_fr": "Dans le détail du crédit, section Échéances, cliquez 'Enregistrer un paiement', saisissez montant, mode de paiement et compte.",
                "question_mg": "Ahoana no handraketana paiement crédit?",
                "answer_mg": "Ao amin'ny détail crédit, section Échéances, tsindrio 'Enregistrer un paiement', fenoy montant, mode de paiement ary compte.",
                "tags": ["credit", "paiement"],
            },
            {
                "id": "manual_014",
                "question_fr": "Quelle différence entre vente rapide, réservation et crédit ?",
                "answer_fr": "Vente rapide: paiement total immédiat. Réservation: acompte + retrait différé. Crédit: paiement échelonné avec échéances.",
                "question_mg": "Inona ny maha samy hafa ny vente rapide, réservation, ary crédit?",
                "answer_mg": "Vente rapide: fandoavana feno avy hatrany. Réservation: acompte + fakana taty aoriana. Crédit: fandoavana mizara échéances.",
                "tags": ["vente", "reservation", "credit"],
            },
            {
                "id": "manual_015",
                "question_fr": "Où voir le dashboard et les statistiques ?",
                "answer_fr": "Le dashboard est accessible via l'onglet principal ou la route /dashboard. Les stats produit sont dans la fiche produit.",
                "question_mg": "Aiza no ahitana dashboard sy statistiques?",
                "answer_mg": "Ny dashboard dia hita amin'ny onglet principal na route /dashboard. Ny statistiques produit dia ao amin'ny fiche produit.",
                "tags": ["dashboard", "statistiques"],
            },
            {
                "id": "manual_016",
                "question_fr": "À quoi servent les transitaires et où voir leur liste ?",
                "answer_fr": "Les transitaires gèrent l'acheminement des réceptions. Pour la liste: /transitaires. Pour créer: /transitaires/nouveau. Pour le détail: /transitaires/:id.",
                "question_mg": "Inona no asan'ny transitaires ary aiza no ahitana ny lisitra?",
                "answer_mg": "Ny transitaires no miandraikitra ny fandefasana ny réception. Lisitra: /transitaires. Famoronana: /transitaires/nouveau. Détail: /transitaires/:id.",
                "tags": ["transitaire", "routes", "reapprovisionnement"],
            },
            {
                "id": "manual_017",
                "question_fr": "Comment noter une réception ?",
                "answer_fr": "Ouvrez la réception puis allez dans l'évaluation: /reapprovisionnements/:id/evaluation. Vous pouvez noter la qualité et ajouter des commentaires.",
                "question_mg": "Ahoana no hanomezana naoty ny réception?",
                "answer_mg": "Sokafy ny réception dia mandehana amin'ny évaluation: /reapprovisionnements/:id/evaluation. Afaka manome naoty kalitao sy manampy fanamarihana ianao.",
                "tags": ["reception", "evaluation", "notation"],
            },
            {
                "id": "manual_018",
                "question_fr": "C'est quoi un client ?",
                "answer_fr": "Un client est une personne ou entreprise qui achète des produits. Dans Express Sale, les clients peuvent avoir des ventes, crédits et réservations liés.",
                "question_mg": "Inona ny atao hoe client?",
                "answer_mg": "Ny client dia olona na orinasa mividy entana. Ao amin'ny Express Sale, ny client dia afaka manana ventes, crédits ary réservations mifandray.",
                "tags": ["clients", "definitions"],
            },
        ]
        result = self.upsert_faq_pairs(pairs=pairs, replace=replace)
        result["source"] = str(MANUAL_PATH)
        result["seed_pairs"] = len(pairs)
        return result

    def search_faq(self, text: str, language: str) -> dict[str, Any] | None:
        where = {"source": "faq"}
        allowed_languages: set[str | None]
        if language in {"fr", "mg"}:
            allowed_languages = {language, "mixed", "unknown", None}
        else:
            allowed_languages = {"fr", "mg", "mixed", "unknown", None}
        # Vector query first to get semantic hints, then lexical rerank on all FAQ docs.
        res = self.collection.query(
            query_texts=[text],
            n_results=30,
            where=where,
            include=["documents", "metadatas", "distances"],
        )
        q_ids = res.get("ids", [[]])
        q_distances = res.get("distances", [[]])
        vector_map: dict[str, float] = {}
        if q_ids and q_ids[0]:
            for cid, dist in zip(q_ids[0], q_distances[0]):
                vector_map[str(cid)] = self._distance_to_score(float(dist))

        data = self.collection.get(where=where, include=["documents", "metadatas"])
        ids = data.get("ids", []) if isinstance(data, dict) else []
        docs = data.get("documents", []) if isinstance(data, dict) else []
        metas = data.get("metadatas", []) if isinstance(data, dict) else []
        if not ids:
            return None

        q_tokens = _normalized_tokens(text)
        q_topics = _detect_topics(q_tokens)
        threshold = FAQ_SHORT_QUERY_THRESHOLD if len(q_tokens) <= 2 else FAQ_MATCH_THRESHOLD
        best: dict[str, Any] | None = None
        best_score = 0.0
        best_overlap = 0
        best_topic_match = False

        for cid, d, m in zip(ids, docs, metas):
            meta = dict(m or {})
            meta_lang = meta.get("language")
            if meta_lang not in allowed_languages:
                continue
            question = str(meta.get("question", "")).strip()
            answer = str(meta.get("answer", "")).strip()
            question_tokens = _normalized_tokens(question)
            full_tokens = _normalized_tokens(" ".join([question, answer, str(d or "")]))
            overlap_q = len(q_tokens & question_tokens)
            overlap_all = len(q_tokens & full_tokens)
            lexical_q = min(1.0, overlap_q / max(len(q_tokens), 1))
            lexical_all = min(1.0, overlap_all / max(len(q_tokens), 1))
            lexical = max(lexical_q, lexical_all * 0.9)
            c_topics = _detect_topics(full_tokens)
            topic_match = bool(q_topics and (q_topics & c_topics))
            topic_bonus = 0.20 if (q_topics and (q_topics & c_topics)) else 0.0
            topic_malus = 0.18 if (q_topics and not (q_topics & c_topics)) else 0.0
            vector_score = vector_map.get(str(cid), 0.0)
            final_score = max(0.0, min(1.0, (0.70 * lexical) + (0.20 * vector_score) + topic_bonus - topic_malus))
            if final_score > best_score:
                best_score = final_score
                best_overlap = overlap_all
                best_topic_match = topic_match
                best = {
                    "doc": {
                        "source": "faq",
                        "language": meta.get("language"),
                        "question": meta.get("question"),
                        "answer": meta.get("answer"),
                        "content": str(d or ""),
                    },
                    "score": final_score,
                }

        relaxed_threshold = threshold - 0.08 if best_topic_match else threshold
        if best_topic_match:
            topic_accept = best and best_score >= FAQ_TOPIC_THRESHOLD and best_overlap >= FAQ_MIN_OVERLAP
            score_accept = best and best_score >= relaxed_threshold and best_overlap >= FAQ_MIN_OVERLAP
            if topic_accept or score_accept:
                return best
            return None

        non_topic_threshold = max(threshold, FAQ_NON_TOPIC_THRESHOLD)
        non_topic_accept = best and best_score >= non_topic_threshold and best_overlap >= max(2, FAQ_MIN_OVERLAP)
        if non_topic_accept:
            return best
        return None

    def search_rag(self, text: str, language: str, top_k: int = 3) -> list[dict[str, Any]]:
        tokens = _normalized_tokens(text)
        route_intent = any(t in ROUTE_INTENT_MARKERS for t in tokens)
        query_topics = _detect_topics(tokens)
        allowed_languages: set[str | None]
        if language in {"fr", "mg"}:
            allowed_languages = {language, "mixed", "unknown", None}
        else:
            allowed_languages = {"fr", "mg", "mixed", "unknown", None}

        if route_intent:
            data = self.collection.get(where={"source": "page_route"}, include=["documents", "metadatas"])
            docs = data.get("documents", []) if isinstance(data, dict) else []
            metas = data.get("metadatas", []) if isinstance(data, dict) else []
            scored: list[dict[str, Any]] = []
            wants_clients = any(t in tokens for t in {"client", "clients"})
            wants_transitaires = any(t in tokens for t in {"transitaire", "transitaires"})
            wants_list = any(t in tokens for t in {"liste", "lisitra", "lister"})

            for doc, meta in zip(docs, metas):
                m = dict(meta or {})
                if m.get("language") not in allowed_languages:
                    continue
                route_text = " ".join(
                    [
                        str(m.get("page_name", "")),
                        str(m.get("page_path", "")),
                        str(doc or ""),
                    ]
                ).lower()
                overlap = sum(1 for t in tokens if t and t in route_text)
                if overlap == 0:
                    continue
                score = min(1.0, 0.20 + overlap * 0.08)
                if wants_clients:
                    if "/clients" in route_text or "client" in route_text:
                        score = min(1.0, score + 0.35)
                    else:
                        score = max(0.0, score - 0.12)
                if wants_transitaires:
                    if "/transitaires" in route_text or "transitaire" in route_text:
                        score = min(1.0, score + 0.35)
                    else:
                        score = max(0.0, score - 0.12)
                if wants_list:
                    if "liste" in route_text or "lister" in route_text:
                        score = min(1.0, score + 0.10)
                if any(t in tokens for t in {"statistique", "statistiques", "dashboard"}):
                    if "/dashboard" in route_text or "statistique" in route_text:
                        score = min(1.0, score + 0.25)
                scored.append(
                    {
                        "score": score,
                        "source": m.get("source"),
                        "language": m.get("language"),
                        "filename": m.get("filename"),
                        "page_path": m.get("page_path"),
                        "page_name": m.get("page_name"),
                        "content": str(doc or ""),
                        "question": m.get("question"),
                        "answer": m.get("answer"),
                    }
                )

            scored.sort(key=lambda x: float(x.get("score", 0.0)), reverse=True)
            if scored:
                return scored[: max(top_k, 1)]

            # Fallback when page routes are not indexed yet in Chroma.
            if PAGE_ROUTES_PATH.exists():
                try:
                    payload = json.loads(PAGE_ROUTES_PATH.read_text(encoding="utf-8"))
                    pages = payload.get("pages", []) if isinstance(payload, dict) else []
                except json.JSONDecodeError:
                    pages = []
                fallback: list[dict[str, Any]] = []
                wants_clients = any(t in tokens for t in {"client", "clients"})
                wants_transitaires = any(t in tokens for t in {"transitaire", "transitaires"})
                wants_list = any(t in tokens for t in {"liste", "lisitra", "lister"})
                for page in pages:
                    if not isinstance(page, dict):
                        continue
                    path = str(page.get("path", "")).strip()
                    name = str(page.get("name", "")).strip()
                    desc = str(page.get("description", "")).strip()
                    keywords = " ".join(page.get("keywords", []) or [])
                    aliases = " ".join(page.get("aliases", []) or [])
                    blob = " ".join([name, path, desc, keywords, aliases]).lower()
                    overlap = sum(1 for t in tokens if t and t in blob)
                    if overlap == 0:
                        continue
                    score = min(1.0, 0.20 + overlap * 0.08)
                    if wants_clients:
                        if "/clients" in blob or "client" in blob:
                            score = min(1.0, score + 0.35)
                        else:
                            score = max(0.0, score - 0.12)
                    if wants_transitaires:
                        if "/transitaires" in blob or "transitaire" in blob:
                            score = min(1.0, score + 0.35)
                        else:
                            score = max(0.0, score - 0.12)
                    if wants_list and ("liste" in blob or "lister" in blob):
                        score = min(1.0, score + 0.10)
                    fallback.append(
                        {
                            "score": score,
                            "source": "page_route",
                            "language": "fr",
                            "filename": None,
                            "page_path": path,
                            "page_name": name,
                            "content": f"La page '{name}' utilise la route '{path}'. Elle sert à: {desc}.",
                            "question": None,
                            "answer": None,
                        }
                    )
                fallback.sort(key=lambda x: float(x.get("score", 0.0)), reverse=True)
                return fallback[: max(top_k, 1)]
            return []

        res = self.collection.query(
            query_texts=[text],
            n_results=30,
            include=["documents", "metadatas", "distances"],
        )
        ids = res.get("ids", [[]])
        if not ids or not ids[0]:
            return []

        out: list[dict[str, Any]] = []
        documents = res.get("documents", [[]])[0]
        metadatas = res.get("metadatas", [[]])[0]
        distances = res.get("distances", [[]])[0]

        for doc, meta, dist in zip(documents, metadatas, distances):
            vector_score = self._distance_to_score(float(dist))
            if vector_score < RAG_MATCH_THRESHOLD:
                continue
            m = dict(meta or {})
            if m.get("source") == "faq":
                continue
            if m.get("language") not in allowed_languages:
                continue
            candidate_text = " ".join(
                [
                    str(m.get("question", "")),
                    str(m.get("answer", "")),
                    str(m.get("page_name", "")),
                    str(m.get("page_path", "")),
                    str(doc or ""),
                ]
            )
            cand_tokens = _normalized_tokens(candidate_text)
            overlap = len(tokens & cand_tokens)
            lexical_score = min(1.0, overlap / max(len(tokens), 1))
            cand_topics = _detect_topics(cand_tokens)
            topic_bonus = 0.0
            topic_malus = 0.0
            if query_topics:
                if query_topics & cand_topics:
                    topic_bonus = 0.25
                else:
                    topic_malus = 0.20

            score = max(0.0, min(1.0, 0.55 * vector_score + 0.45 * lexical_score + topic_bonus - topic_malus))
            route_bonus = 0.0
            if route_intent and m.get("source") == "page_route":
                route_text = " ".join(
                    [
                        str(m.get("page_name", "")),
                        str(m.get("page_path", "")),
                        str(doc or ""),
                    ]
                ).lower()
                overlap = sum(1 for t in tokens if t and t in route_text)
                route_bonus = min(0.20, overlap * 0.03)
                score = min(1.0, score + route_bonus)
            if score < 0.30:
                continue
            out.append(
                {
                    "score": score,
                    "source": m.get("source"),
                    "language": m.get("language"),
                    "filename": m.get("filename"),
                    "page_path": m.get("page_path"),
                    "page_name": m.get("page_name"),
                    "content": str(doc or ""),
                    "question": m.get("question"),
                    "answer": m.get("answer"),
                }
            )
        out.sort(key=lambda x: float(x.get("score", 0.0)), reverse=True)
        return out[: max(top_k, 1)]

    def _count(self, where: dict[str, Any]) -> int:
        data = self.collection.get(where=where, include=[])
        ids = data.get("ids", []) if isinstance(data, dict) else []
        return len(ids)

    def status(self) -> dict[str, Any]:
        faq_fr = self._count({"$and": [{"source": "faq"}, {"language": "fr"}]})
        faq_mg = self._count({"$and": [{"source": "faq"}, {"language": "mg"}]})
        pdf_count = self._count({"source": "pdf"})
        page_routes_count = self._count({"source": "page_route"})
        total_docs = self.collection.count()
        return {
            "vector_store": "chroma",
            "faq_pairs": len(self.faq_pairs),
            "faq": {"fr": faq_fr, "mg": faq_mg},
            "pdf_chunks": pdf_count,
            "page_route_chunks": page_routes_count,
            "total_docs": total_docs,
            "chroma_dir": str(CHROMA_DIR),
            "collection": CHROMA_COLLECTION,
        }


def build_response(language: str, question: str, docs: list[dict[str, Any]]) -> str:
    q = question.lower().strip()
    if any(k in q for k in ["qui etes vous", "qui êtes vous", "qui es tu", "qui es-tu", "iza ianao"]):
        if language == "mg":
            return "Mpanampy AI ho an'ny Express Sale aho. Afaka manampy amin'ny pejy, stock, ventes, crédits ary réservations."
        return "Je suis l'assistant AI d'Express Sale. Je peux vous aider sur les pages, le stock, les ventes, les crédits et les réservations."
    if not docs:
        if language == "mg":
            return "Mbola tsy nahita valiny tao amin'ny FAQ na PDF aho. Ampio FAQ/PDF aloha."
        return "Je n'ai pas trouvé de réponse dans la FAQ ou les PDF. Ajoutez du contenu FAQ/PDF."

    top = docs[0]
    if top.get("source") == "faq" and top.get("answer"):
        return str(top["answer"])

    if top.get("source") == "db":
        data = top.get("data") or {}
        if data.get("error"):
            if language == "mg":
                return f"Tsy afaka mifandray amin'ny base de données: {data.get('error')}"
            return f"Impossible d'accéder à la base de données en temps réel: {data.get('error')}"
        rows = data.get("rows") or []
        desc = data.get("description_mg") if language == "mg" else data.get("description_fr")
        if data.get("action") == "count" and rows:
            total = rows[0].get("total", 0)
            if language == "mg":
                return f"{desc} Isan'ny total: {total}."
            return f"{desc} Total: {total}."
        if data.get("action") == "schema" and rows:
            header = desc or ("Schéma de la table:" if language != "mg" else "Firafitry ny table:")
            lines = []
            for r in rows:
                line = f"- {r.get('column_name')}: {r.get('data_type')} (nullable={r.get('is_nullable')})"
                lines.append(line)
            return f"{header}\n" + "\n".join(lines)
        if not rows:
            if language == "mg":
                return "Tsy misy valiny hita ao amin'ny base de données amin'izao fotoana izao."
            return "Aucun résultat trouvé dans la base de données."
        preview = rows[:10]
        lines = []
        for r in preview:
            items = ", ".join([f"{k}: {v}" for k, v in r.items()])
            lines.append(f"- {items}")
        header = desc or ("Résultats base de données:" if language != "mg" else "Vokatry ny base de données:")
        suffix = "" if len(rows) <= len(preview) else f"\n(+{len(rows) - len(preview)} autres résultats)"
        return f"{header}\n" + "\n".join(lines) + suffix

    if docs and docs[0].get("source") == "page_route":
        intro = "Voici les pages les plus pertinentes :" if language != "mg" else "Ireto ny pejy mifanaraka indrindra:"
        lines = [intro]
        for d in docs[:3]:
            name = d.get("page_name") or "Page"
            path = d.get("page_path") or "-"
            content = str(d.get("content", ""))
            desc = ""
            m = re.search(r"Elle sert à:\s*(.*?)\.\s*Fonctions principales", content)
            if m:
                desc = m.group(1).strip()
            if not desc:
                desc = content[:120].strip()
            if language == "mg":
                lines.append(f"- {name} ({path}) : {desc}")
            else:
                lines.append(f"- {name} ({path}) : {desc}")
        if language == "mg":
            lines.append("Raha tianao dia afaka milaza pejy iray manokana aho hanokafana azy mivantana.")
        else:
            lines.append("Si vous voulez, je peux vous recommander la meilleure route exacte selon votre objectif.")
        return "\n".join(lines)

    context = "\n".join(d.get("content", "")[:300] for d in docs)
    if language == "mg":
        return f"Ity no valiny mifototra amin'ny antontan-taratasy:\n{context}"
    return f"Voici la réponse basée sur les documents:\n{context}"


_store: RagStore | None = None


def get_store() -> RagStore:
    global _store
    if _store is None:
        _store = RagStore()
    return _store
def _match_alias(tokens: set[str], aliases: set[str]) -> bool:
    for alias in aliases:
        alias_tokens = {_normalize_token(t) for t in alias.split() if t}
        if alias_tokens and alias_tokens.issubset(tokens):
            return True
    return False


def _is_definition_question(text: str) -> bool:
    tokens = _normalized_tokens(text)
    if not tokens:
        return False
    if {"c", "est", "quoi"} <= tokens or {"qu", "est", "ce", "que"} <= tokens:
        return True
    return bool(tokens & DEFINITION_MARKERS)
