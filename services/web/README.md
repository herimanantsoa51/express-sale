# Express Sale — Frontend

> Application de gestion commerciale (POS, stock, trésorerie, clients, fournisseurs).
> **Stack** : React 19 + Vite 7 + Axios + Recharts + Lucide Icons

---

## Table des matières

1. [Prérequis](#prérequis)
2. [Installation](#installation)
3. [Scripts disponibles](#scripts-disponibles)
4. [Architecture du projet](#architecture-du-projet)
5. [Configuration réseau et API](#configuration-réseau-et-api)
6. [Authentification](#authentification)
7. [Routing et contrôle d accès](#routing-et-contrôle-daccès)
8. [Services couche API](#services-couche-api)
9. [Hooks personnalisés](#hooks-personnalisés)
10. [Composants réutilisables](#composants-réutilisables)
11. [Pages par domaine](#pages-par-domaine)
12. [Styles](#styles)
13. [Dépendances](#dépendances)
14. [Build et déploiement](#build-et-déploiement)
15. [Notes pour les devs](#notes-pour-les-devs)

---

## Prérequis

| Outil   | Version minimale |
|---------|-----------------|
| Node.js | 18+             |
| npm     | 9+              |

Le backend Laravel doit tourner sur le port **8000** (configurable dynamiquement).

---

## Installation

```bash
cd frontend
npm install
npm run dev        # → http://localhost:3000
```

Le serveur Vite écoute sur `0.0.0.0:3000` (accessible sur le réseau local).

---

## Scripts disponibles

| Commande          | Description                          |
|-------------------|--------------------------------------|
| `npm run dev`     | Serveur de développement (port 3000) |
| `npm run build`   | Build de production dans `dist/`     |
| `npm run preview` | Prévisualiser le build               |
| `npm run lint`    | Linter ESLint                        |

---

## Architecture du projet

```
src/
├── main.jsx                  # Point d'entrée React
├── App.jsx                   # Router principal + providers
├── assets/                   # Images, icônes statiques
├── config/                   # (vide, config dans api.js)
├── context/                  # Contextes React globaux
│   ├── AuthContext.jsx       # État auth global (user, login, logout)
│   └── ThemeContext.jsx      # Thème clair/sombre
├── hooks/                    # Hooks React personnalisés
│   ├── useAccounts.js
│   ├── useActivityLogs.js
│   ├── useCompanyInfo.js
│   ├── useCreditDetail.js
│   ├── useCredits.js
│   ├── useImmediateSales.js
│   ├── useNotificationGenerator.js
│   ├── useReservations.js
│   └── useUsers.js
├── services/                 # Couche d'abstraction API (Axios)
│   ├── api.js                # Instance Axios centrale (interceptors, token)
│   ├── authService.js
│   ├── productService.js
│   ├── saleService.js
│   └── ... (voir section Services)
├── components/               # Composants réutilisables
│   ├── common/               # UI atoms (Button, Card, Input, Pagination...)
│   ├── layout/               # Header, Sidebar, Layout
│   ├── modals/               # Modals réutilisables
│   ├── Sales/                # Composants liés aux ventes
│   ├── Credits/              # Composants liés aux crédits
│   ├── reservations/         # Composants liés aux réservations
│   ├── notifications/        # Cloche de notification, modals
│   ├── statistics/           # Graphiques, tables de stats
│   ├── dashboard/            # Widgets du dashboard
│   ├── Accounts/             # Composants comptes
│   ├── AccountSelector/      # Sélecteur de comptes
│   ├── ActivityLogs/         # Tableau et modal logs activité
│   ├── CoordinateSelect.jsx  # Sélecteur pays/ville
│   ├── ClientQuickCreateForm.jsx
│   ├── ProtectedRoute.jsx    # Garde authentification
│   └── RoleRoute.jsx         # Garde de rôle (admin, vendeur)
├── pages/                    # Pages (1 dossier = 1 domaine métier)
│   ├── Dashboard/
│   ├── Products/
│   ├── Sales/
│   ├── Customers/
│   ├── Suppliers/
│   ├── FreightForwarders/
│   ├── StockReceipt/
│   ├── StockMovement/
│   ├── Locations/
│   ├── ProductVariantLocations/
│   ├── Accounts/
│   ├── Expenses/
│   ├── Transactions/
│   ├── Statistics/
│   ├── Users/
│   ├── CashCount/
│   ├── Notification/
│   ├── ActivityLogs/
│   ├── Settings/
│   ├── Login.jsx
│   └── Unauthorized.jsx
├── styles/                   # Fichiers CSS (1 CSS par page/composant)
│   ├── variables.css         # Variables CSS globales
│   ├── reset.css             # Reset CSS
│   ├── global.css            # Styles globaux
│   ├── customToastify.css    # Override react-toastify
│   ├── components/           # CSS des composants
│   ├── Sales/                # CSS des pages ventes
│   └── *.css                 # 1 fichier par page/composant
└── utils/
    └── formatters.js         # formatCurrency, formatDate, formatNumber...
```

---

## Configuration réseau et API

Le fichier `services/api.js` crée une instance Axios centralisée :

- **Détection automatique** de l'URL API basée sur `window.location.hostname`
- Si `localhost` → `http://localhost:8000/api`
- Sinon → `http://<IP_COURANTE>:8000/api`
- **Interceptor requête** : injecte le token Bearer depuis `localStorage`
- **Interceptor réponse** :
  - `401` → nettoyage localStorage + redirection `/login`
  - `403` → log erreur accès refusé
  - `500+` → log erreur serveur

**Tous les services** importent cette instance unique `api`.

---

## Authentification

| Élément          | Fichier                        | Description                                      |
|------------------|--------------------------------|--------------------------------------------------|
| AuthContext      | `context/AuthContext.jsx`      | Provider React — expose `user`, `login`, `logout` |
| authService      | `services/authService.js`      | Appels `/auth/login`, `/auth/logout`             |
| ProtectedRoute   | `components/ProtectedRoute.jsx`| Redirige vers `/login` si non authentifié        |
| RoleRoute        | `components/RoleRoute.jsx`     | Redirige vers `/unauthorized` si rôle insuffisant|

**Flux** :
1. `POST /auth/login` → reçoit `token` + `user`
2. Stockage dans `localStorage` (`auth_token`, `user`)
3. Injection automatique du token via interceptor Axios
4. Déconnexion : `POST /auth/logout` + nettoyage localStorage

**Rôles** : `admin`, `vendeur`

---

## Routing et contrôle d accès

Défini dans `App.jsx`. Toutes les routes sont sous `<ProtectedRoute>` + `<Layout>`.

| Route                                | Page                         | Rôles autorisés     |
|--------------------------------------|------------------------------|---------------------|
| `/dashboard`                         | Dashboard                    | tous                |
| `/produits`                          | Liste produits               | tous                |
| `/produits/nouveau`                  | Créer produit                | admin, vendeur      |
| `/produits/:id`                      | Détails produit              | tous                |
| `/produits/:id/modifier`             | Modifier produit             | admin, vendeur      |
| `/produits/:productId/variante/*`    | Créer/modifier variante      | admin, vendeur      |
| `/fournisseurs/**`                   | CRUD fournisseurs            | admin               |
| `/transitaires/**`                   | CRUD transitaires            | admin               |
| `/reapprovisionnements/**`           | Réceptions de stock          | admin               |
| `/localisations-variantes/**`        | Localisations variantes      | tous (écriture: admin) |
| `/locations/:locationId/variantes`   | Vue variantes par location   | tous                |
| `/mouvements-stock/**`               | Mouvements, transferts, pertes | admin             |
| `/locations/**`                      | CRUD locations               | tous (écriture: admin) |
| `/clients/**`                        | Liste/détails clients        | admin, vendeur      |
| `/ventes/rapide`                     | POS (vente rapide)           | tous                |
| `/ventes/immediates/**`              | Liste/détails ventes immédiates | tous             |
| `/ventes/credits/**`                 | Liste/détails crédits        | tous                |
| `/ventes/reservations/**`            | Liste/détails réservations   | tous                |
| `/comptes/**`                        | Trésorerie (comptes)         | admin               |
| `/depenses/**`                       | Dépenses + planifiées        | admin               |
| `/transactions/:id`                  | Détail transaction           | tous                |
| `/statistiques`                      | Statistiques de vente        | admin               |
| `/statistiques/financieres`          | Statistiques financières     | admin               |
| `/utilisateurs`                      | Gestion utilisateurs         | admin               |
| `/comptages/**`                      | Comptage de caisse           | tous                |
| `/parametres`                        | Configuration entreprise     | admin, vendeur      |
| `/journaux-activite`                 | Journaux activité            | admin               |

---

## Services couche API

Chaque service encapsule les appels HTTP vers un domaine métier backend.

| Service                         | Fichier                             | Domaine                                   |
|---------------------------------|-------------------------------------|-------------------------------------------|
| authService                     | `authService.js`                    | Authentification                          |
| productService                  | `productService.js`                 | Produits, variantes, catégories, attributs|
| categoryService                 | `categoryService.js`                | Catégories (CRUD dédié)                   |
| saleService                     | `saleService.js`                    | Création ventes (immédiate, crédit, réservation) |
| immediateSaleService            | `immediateSaleService.js`           | Liste/détail ventes immédiates            |
| creditService                   | `creditService.js`                  | Crédits + paiements échéances             |
| reservationsService             | `reservationsService.js`            | Réservations (compléter, annuler)         |
| customerService                 | `customerService.js`                | Clients                                   |
| accountService                  | `accountService.js`                 | Comptes monétaires + transferts           |
| transactionService              | `transactionService.js`             | Détail/annulation de transactions         |
| currencyService                 | `currencyService.js`                | Taux de change                            |
| supplierService                 | `supplierService.js`                | Fournisseurs                              |
| freightForwarderService         | `freightForwarderService.js`        | Transitaires                              |
| coordinateService               | `coordinateService.js`              | Coordonnées (pays/villes)                 |
| stockReceiptService             | `stockReceiptService.js`            | Réceptions stock (workflow complet)       |
| stockPaymentService             | `stockPaymentService.js`            | Paiements fournisseur/transitaire         |
| stockMovementService            | `stockMovementService.js`           | Mouvements de stock                       |
| locationService                 | `locationService.js`                | Emplacements de stockage                  |
| productVariantLocationService   | `productVariantLocationService.js`  | Variantes par emplacement                 |
| dashboardService                | `dashboardService.js`               | Données dashboard                         |
| statisticsService               | `statisticsService.js`              | Statistiques ventes + finances            |
| expenseService                  | `expenseService.js`                 | Dépenses opérationnelles                  |
| plannedExpenseService           | `plannedExpenseService.js`          | Dépenses planifiées                       |
| invoiceService                  | `invoiceService.js`                 | Téléchargement factures PDF               |
| printService                    | `printService.js`                   | Impression tickets (ESC/POS)              |
| cashCountService                | `cashCountService.js`               | Comptage de caisse                        |
| notificationsService            | `notificationsService.js`           | Notifications                             |
| companyInfoService              | `companyInfoService.js`             | Infos entreprise                          |
| activityLogsService             | `activityLogsService.js`            | Journaux activité                         |
| usersService                    | `usersService.js`                   | Gestion utilisateurs                      |
| fileService                     | `fileService.js`                    | Upload/suppression images                 |

---

## Hooks personnalisés

| Hook                        | Usage                                           |
|-----------------------------|------------------------------------------------|
| useAccounts                 | Chargement et gestion des comptes monétaires   |
| useActivityLogs             | Chargement paginé des journaux activité        |
| useCompanyInfo              | Infos entreprise (header)                      |
| useCreditDetail             | Détail crédit avec échéances                   |
| useCredits                  | Liste paginée/filtrée des crédits              |
| useImmediateSales           | Liste paginée/filtrée des ventes immédiates    |
| useNotificationGenerator    | Génération périodique de notifications          |
| useReservations             | Liste paginée/filtrée des réservations         |
| useUsers                    | Chargement liste utilisateurs                  |

---

## Composants réutilisables (components/common/)

| Composant         | Description                                     |
|-------------------|-------------------------------------------------|
| Button            | Bouton stylisé (variantes, loading, icône)      |
| Card              | Carte conteneur                                 |
| Input             | Champ de saisie stylisé                         |
| AppleButton       | Bouton style Apple                              |
| AppleCard         | Carte style Apple                               |
| AppleBadge        | Badge style Apple                               |
| AppleSelect       | Select style Apple                              |
| Pagination        | Composant de pagination                         |
| EmptyState        | État vide illustré                              |
| SkeletonLoader    | Loader de chargement (skeleton)                 |

---

## Pages par domaine

### Dashboard
- `Dashboard.jsx` — Vue ensemble : stats ventes, top produits, tendances, dépenses

### Produits
- `ProductsList.jsx` — Liste avec filtres/recherche
- `ProductDetails.jsx` — Détails produit + variantes + lots
- `ProductForm.jsx` — Création/modification produit
- `VariantForm.jsx` — Création/modification variante

### Ventes
- `QuickSalePage.jsx` — **POS** : vente rapide (immédiate, crédit, réservation)
- `ImmediateSalesList.jsx` / `ImmediateSaleDetail.jsx` — Ventes immédiates
- `CreditListPage.jsx` / `CreditDetailPage.jsx` — Crédits + paiements
- `ReservationsPage.jsx` / `ReservationDetailPage.jsx` — Réservations

### Clients
- `CustomerList.jsx` — Liste clients
- `CustomerDetails.jsx` — Détails + historique (ventes, crédits, réservations)

### Fournisseurs et Transitaires
- `SupplierList.jsx` / `SupplierDetails.jsx` / `SupplierForm.jsx`
- `FreightForwardersList.jsx` / `FreightForwarderDetails.jsx` / `FreightForwarderForm.jsx`

### Réapprovisionnement (Stock Receipts)
- `StockReceiptList.jsx` — Liste des réceptions
- `StockReceiptForm.jsx` — Création commande fournisseur
- `StockReceiptDetails.jsx` — Détails + workflow (envoyé > transit > arrivé > évalué > coûts répartis)
- `StockReceiptRating.jsx` — Évaluation qualité items reçus
- `StockReceiptPayment.jsx` — Paiements fournisseur/transitaire
- `CostAllocation.jsx` / `CostAllocationView.jsx` — Répartition coûts par produit

### Locations et Mouvements de stock
- `LocationsList.jsx` / `LocationDetail.jsx` / `LocationForm.jsx`
- `ProductVariantLocationsList.jsx` / `ProductVariantLocationForm.jsx` / `LocationVariantsView.jsx`
- `StockMovementList.jsx` / `StockTransferForm.jsx` / `StockLossForm.jsx` / `InventoryReconciliationForm.jsx`

### Trésorerie
- `AccountsList.jsx` / `AccountDetail.jsx` / `AccountForm.jsx`
- `AccountTransfer.jsx` — Transfert entre comptes
- `CurrencyRates.jsx` — Gestion taux de change

### Statistiques
- `SaleStatisctics.jsx` — Stats ventes (timeline, top produits, par catégorie/vendeur/paiement)
- `FinancialStatistics.jsx` — Stats financières (profits, dépenses, pertes)

### Dépenses
- `ExpenseList.jsx` / `ExpenseCreate.jsx` — Dépenses opérationnelles
- `PlannedExpensesList.jsx` / `PlannedExpenseDetail.jsx` / `PlannedExpenseCreate.jsx`

### Comptage de caisse
- `CashCountList.jsx` / `CashCountForm.jsx` / `CashCountDetail.jsx`

### Paramètres et Administration
- `CompanyConfiguration.jsx` — Infos entreprise + infos réseau
- `UsersPage.jsx` — CRUD utilisateurs
- `ActivityLogs.jsx` — Journaux activité (consultation, nettoyage)

---

## Styles

- **Approche** : CSS classique (1 fichier CSS par page/composant)
- **Variables CSS** dans `styles/variables.css` (couleurs, bordures, ombres, tailles)
- **Reset CSS** dans `styles/reset.css`
- **Thème** : clair/sombre géré via `ThemeContext`
- **Toastify** : override dans `styles/customToastify.css`

---

## Dépendances

| Package           | Version  | Usage                                    |
|-------------------|----------|------------------------------------------|
| react             | ^19.2.0  | Framework UI                             |
| react-dom         | ^19.2.0  | Rendu DOM                                |
| react-router-dom  | ^7.11.0  | Routing SPA                              |
| axios             | ^1.13.2  | Client HTTP                              |
| recharts          | ^3.6.0   | Graphiques (dashboard, statistiques)     |
| lucide-react      | ^0.562.0 | Icônes SVG                               |
| react-toastify    | ^11.0.5  | Notifications toast                      |
| date-fns          | ^4.1.0   | Formatage de dates                       |
| qrcode            | ^1.5.4   | Génération de QR codes (page réseau)     |

---

## Build et déploiement

```bash
npm run build
```

Produit un dossier `dist/` prêt pour un serveur statique (Nginx, Apache...).

**Docker** : un `Dockerfile` et `nginx.conf` sont fournis pour le déploiement conteneurisé.

```bash
docker build -t express-sale-frontend .
docker run -p 3000:80 express-sale-frontend
```

---

## Notes pour les devs

### Code nettoyé (supprimé lors de l audit)
Les éléments suivants ont été identifiés comme **code mort** et supprimés :
- `src/App.css` — CSS par défaut Vite, jamais importé
- `src/services/NetworkConfigService.js` — Service jamais importé par aucun composant
- `src/hooks/useNotificationSound.js` — Hook jamais importé
- `src/utils/validators.js` — Utilitaire jamais importé
- `src/pages/Locations/LocationDetail.jsx.backup` — Fichier backup
- Import inutile `import { data } from 'react-router-dom'` dans `stockReceiptService.js`
- Méthode morte `validateCosts()` dans `stockReceiptService.js` (endpoint backend inexistant)
- **Packages npm désinstallés** : `three`, `motion`, `dayjs`, `chart.js`, `react-chartjs-2`

### Services avec méthodes définies mais jamais appelées
Ces méthodes existent dans les services mais ne sont utilisées dans aucun composant/page. Elles sont prêtes pour un usage futur :
- `reservationsService.exportCSV()` / `reservationsService.exportPDF()` — routes backend inexistantes aussi
- `productService.getProductStatistics()` — endpoint existe côté backend

### Conventions
- **Services** : un fichier par domaine métier, toutes les méthodes sont `async`
- **Pages** : un dossier par module métier, composants locaux co-localisés
- **CSS** : pas de CSS modules ni de CSS-in-JS, 1 fichier CSS par composant/page
- **Toast** : utiliser `react-toastify` pour les notifications utilisateur
- **Console.log** : nombreux `console.log` de debug dans le code — à nettoyer pour la production
