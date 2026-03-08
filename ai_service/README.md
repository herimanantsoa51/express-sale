# Express Sale AI Service (FastAPI)

## Lancer en local
```bash
cd ai_service
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --host 0.0.0.0 --port 9000 --reload
```

## Endpoints
- `POST /tasks` : générer des tâches AI (JSON)
- `POST /chat` : chat synchrone
- `POST /rag/refresh` : indexer routes frontend + API
- `POST /rag/query` : interroger le RAG

## Où est le RAG ?
Le RAG est dans `ai_service/app/rag.py`.

Il indexe automatiquement :
1. Les routes frontend depuis `frontend/src/App.jsx`
2. Les routes API depuis `backend/routes/api.php`
3. Les pages décrites dans `ai_service/knowledge/page_routes.json` (prioritaire)

Après démarrage du service, exécute :
```bash
curl -X POST http://localhost:9000/rag/refresh
```

Ce RAG donne au modèle la liste des pages + endpoints disponibles.

## Comment le modèle sait ce que fait une API ?
Par défaut, on ne connaît que **le chemin** (ex: `POST /api/products`).
Pour expliquer ce que fait une API, on peut enrichir le RAG avec des descriptions :

Option recommandée:
- Ajouter un fichier `ai_service/knowledge/api_descriptions.json`
- Exemple :
```json
{
  "POST /products": "Créer un produit",
  "GET /products": "Lister les produits",
  "GET /customers": "Lister les clients"
}
```

Ensuite, on enrichit l’index RAG avec ces descriptions.

## Comment le modèle sait ce que contient une page ?
On définit les pages dans `ai_service/knowledge/page_routes.json` :
- `path`, `name`, `description`
- `highlights` (points clés)
- `actions` (actions possibles)
- `roles` (rôles recommandés)

## Variables
- `CHROMA_PERSIST_DIR` : chemin de persistance Chroma (par défaut `ai_service/chroma`)
