# LangGraph Service - README

## 1) Objectif

Ce service gère un assistant FR/MG avec ce flux:

1. Détecter la langue (`fr`, `mg`, ou `unknown`)
2. Chercher d'abord dans la FAQ de la langue détectée
3. Si pas de match FAQ, chercher dans les PDF indexés (RAG local)
4. Retourner une réponse (FAQ directe ou réponse basée sur chunks PDF)

Le backend Laravel appelle ce service via HTTP.

## 2) Architecture Technique

### Composants

- API FastAPI: [main.py](/home/christian/Programming/express-sale/langgraph_service/main.py)
- Graphe LangGraph: [graph.py](/home/christian/Programming/express-sale/langgraph_service/app/graph.py)
- Logique RAG/FAQ/langue: [chains.py](/home/christian/Programming/express-sale/langgraph_service/app/chains.py)

### Données stockées (actuel)

- FAQ FR: `langgraph_service/data/faqs_fr.json`
- FAQ MG: `langgraph_service/data/faqs_mg.json`
- Index vectoriel (FAQ + PDF chunks): `langgraph_service/data/chroma/` (ChromaDB)
- PDF uploadés: `langgraph_service/data/pdf_uploads/`

Important: actuellement, ce service n'utilise pas PostgreSQL directement.  
L'historique conversation est stocké côté Laravel dans `ai_tasks` (modèle `langgraph-rag-v1`).

Vector store final: **ChromaDB** (collection `express_sale_rag`).

Base de connaissances indexée:
- FAQ FR/MG
- PDF chunkés
- `ai_service/knowledge/page_routes.json` chunké via `POST /rag/page-routes`

FAQ recommandées:
- stocker une seule entrée canonique par FAQ avec 2 traductions (`fr`, `mg`)
- indexer les 2 variantes linguistiques dans Chroma à partir de la même paire
- réduire drastiquement la duplication métier

## 3) Détection de langue FR/MG: quel modèle est utilisé ?

Actuellement, la détection utilise **fastText Meta** (`lid.176`).

- Fonction: `detect_language_with_models(...)` dans [chains.py](/home/christian/Programming/express-sale/langgraph_service/app/chains.py)
- Méthode principale:
  - modèle `lid.176.bin` (ou `lid.176.ftz`) de fastText,
  - extraction des probabilités `fr` et `mg`,
  - si les deux scores sont proches et suffisamment élevés, label `mixed`.
- Fallback minimal:
  - si le modèle n'est pas disponible, heuristique locale FR/MG (marqueurs) pour garder le service fonctionnel.
- Labels possibles: `fr`, `mg`, `mixed`, `unknown`.

Donc le coût API externe de détection reste **0**.

## 4) Flow A a Z d'une requête

1. Laravel reçoit `POST /api/ai/langgraph/query` avec:
   - `session_id` (obligatoire)
   - `text`
   - `context` optionnel
2. Laravel persiste un log `ai_tasks` (status `processing`).
3. Laravel envoie la requête à `langgraph_service /query`.
4. LangGraph exécute:
   - `detect_language`
   - `retrieve_faq`
   - si match: `finalize_faq`
   - sinon: `retrieve_rag` -> `generate_response`
5. Réponse renvoyée à Laravel.
6. Laravel met à jour `ai_tasks` (status `completed`, `raw_response`, message assistant).
7. Le frontend peut lire les messages via:
   - `GET /api/ai/langgraph/messages?session_id=...`

## 5) Endpoints LangGraph Service (Python)

- `GET /health`
- `GET /graph/mermaid`
- `POST /query`
- `POST /rag/faqs`
- `POST /rag/faqs/pairs`
- `POST /rag/pdfs`
- `POST /rag/page-routes`
- `POST /rag/bootstrap/manual-faqs`
- `GET /rag/status`

## 6) Endpoints Laravel (bridge)

- `POST /api/ai/langgraph/query`
- `POST /api/ai/langgraph/rag/faqs`
- `POST /api/ai/langgraph/rag/faqs/pairs`
- `POST /api/ai/langgraph/rag/pdfs`
- `POST /api/ai/langgraph/rag/page-routes`
- `GET /api/ai/langgraph/rag/status`
- `GET /api/ai/langgraph/conversations`
- `GET /api/ai/langgraph/messages`

## 7) Stats gains tokens/couts (estimation)

## Hypothese

- `N` = nombre de messages utilisateur
- `h` = taux de réponses traitées par FAQ/RAG local sans appel LLM externe
- `T` = tokens moyens par requête si on envoie tout au LLM (input + output)
- `P` = coût moyen par token (ou coût/million tokens)

### Sans routage (tout vers LLM)

- Tokens: `N * T`
- Coût: `N * T * P`

### Avec détection + FAQ/RAG local

- Détection locale: coût 0 token externe
- Seules les requêtes non résolues localement vont au LLM: `(1 - h) * N`
- Tokens: `(1 - h) * N * T`
- Coût: `(1 - h) * N * T * P`

### Gain

- Gain tokens (%) = `h * 100`
- Gain coût (%) = `h * 100`

### Exemple concret

- `N = 10 000` messages/mois
- `T = 1 550` tokens/message
- Cas A: `h = 60%` -> gain ~`60%`
- Cas B: `h = 75%` -> gain ~`75%`

Donc si la majorité des questions sont couvertes par FAQ/PDF, le gain est souvent entre **60% et 75%**, parfois plus.

## 10) Configuration Détection (fastText)

Placer le fichier modèle dans:

- `langgraph_service/data/lid.176.bin` (recommandé)
- ou `langgraph_service/data/lid.176.ftz`

Source officielle Meta fastText:
- https://fasttext.cc/docs/en/language-identification.html

Installation dépendance:

```bash
pip install -r langgraph_service/requirements.txt
```

## 8) Limitations actuelles

- Détection langue dépend du fichier modèle fastText `lid.176` présent localement
- Embedding actuel du vector store = hash embedding local (pas embedding SOTA)
- Pas de génération LLM avancée branchée ici (réponse locale basée sur contenu)

## 9) Démarrage local

Depuis la racine projet:

```bash
pip install -r langgraph_service/requirements.txt
uvicorn langgraph_service.main:app --reload --port 8001
```

Configurer Laravel:

```env
LANGGRAPH_SERVICE_URL=http://localhost:8001
```

## 11) Script de test API (CLI)

Script:
- `langgraph_service/scripts/langgraph_cli_test.py`

Exemples:

```bash
python langgraph_service/scripts/langgraph_cli_test.py health
python langgraph_service/scripts/langgraph_cli_test.py status
python langgraph_service/scripts/langgraph_cli_test.py ingest-routes
python langgraph_service/scripts/langgraph_cli_test.py bootstrap-manual-faqs --replace
python langgraph_service/scripts/langgraph_cli_test.py add-faq --language fr --question "Comment créer un produit ?" --answer "Produits > Nouveau"
python langgraph_service/scripts/langgraph_cli_test.py add-faq-pair --question-fr "Comment créer un produit ?" --answer-fr "Produits > Nouveau" --question-mg "Ahoana no hamoronana vokatra?" --answer-mg "Produits > Nouveau"
python langgraph_service/scripts/langgraph_cli_test.py query --text "je veux aller sur produits" --debug
```

Avec `--debug`, `/query` retourne la trace détaillée des étapes du graphe:
- langue détectée + scores,
- résultat FAQ + score,
- résultat RAG (nombre de documents, top score),
- source finale de réponse.
