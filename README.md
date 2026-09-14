# Express Sale - Architecture Organisée

Ce projet est une solution complète de gestion de vente.

## Structure du Projet

```text
/express-sale
├── services/
│   ├── api/        # Backend Laravel (PHP 8.3)
│   └── web/        # Frontend React (Vite)
├── docs/           # Documentation et Manuels
├── scripts/        # Scripts d'administration et base de données
├── docker-compose.yml
└── README.md
```

## Démarrage Rapide

Pour lancer l'ensemble des services :

```bash
docker compose up -d --build
```

- **Frontend :** [http://localhost:3000](http://localhost:3000)
- **Backend API :** [http://localhost:8000](http://localhost:8000)
- **Serveur MCP :** `POST http://localhost:8000/mcp/express-sale` (auth bearer Sanctum)

## Documentation

Consultez le dossier `docs/` pour plus de détails sur l'installation et le fonctionnement de l'application.
