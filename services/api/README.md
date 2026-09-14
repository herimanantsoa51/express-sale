# Express Sale — Backend

> API REST pour application de gestion commerciale (POS, stock, trésorerie, clients, fournisseurs).
> **Stack** : Laravel 12 + Sanctum + SQLite + DomPDF + ESC/POS

---

## Table des matières

1. [Prérequis](#prérequis)
2. [Installation](#installation)
3. [Scripts disponibles](#scripts-disponibles)
4. [Architecture du projet](#architecture-du-projet)
5. [Base de données](#base-de-données)
6. [Authentification et autorisation](#authentification-et-autorisation)
7. [Controllers](#controllers)
8. [Services métier](#services-métier)
9. [Modèles Eloquent](#modèles-eloquent)
10. [Enums](#enums)
11. [Form Requests](#form-requests)
12. [Resources API](#resources-api)
13. [Middleware](#middleware)
14. [Commandes Artisan](#commandes-artisan)
15. [Tâches planifiées](#tâches-planifiées)
16. [Observers et Traits](#observers-et-traits)
17. [Helpers](#helpers)
18. [Seeders](#seeders)
19. [Configuration](#configuration)
20. [Génération PDF et impression](#génération-pdf-et-impression)
21. [Notifications](#notifications)
22. [Routes API](#routes-api)
23. [Dépendances](#dépendances)
24. [Docker et déploiement](#docker-et-déploiement)
25. [Notes pour les devs](#notes-pour-les-devs)

---

## Prérequis

| Outil       | Version minimale |
|-------------|-----------------|
| PHP         | 8.2+            |
| Composer    | 2.x             |
| SQLite      | 3.x             |
| Node.js     | 18+ (pour Vite) |

---

## Installation

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve --host=0.0.0.0 --port=8000
```

Ou via le script de démarrage :

```bash
chmod +x start.sh
./start.sh    # Détecte l'IP et lance le serveur
```

---

## Scripts disponibles

| Commande              | Description                                          |
|-----------------------|------------------------------------------------------|
| `composer setup`      | Install complet (composer, .env, migrate, npm build) |
| `composer dev`        | Dev : serveur + queue + logs + vite en parallèle     |
| `composer test`       | Exécuter les tests PHPUnit                           |
| `php artisan serve`   | Serveur de développement (port 8000)                 |

---

## Architecture du projet

```
app/
├── Bootstrap/
│   └── StartupCatchup.php          # Rattrapage des tâches au démarrage
├── Casts/
│   └── FullUrl.php                 # Cast pour URLs complètes (images)
├── Console/
│   ├── Kernel.php                  # Planification des tâches cron
│   └── Commands/
│       ├── CatchUpMissedTasksCommand.php   # Rattraper les tâches manquées
│       ├── ExpireReservations.php          # Expirer les réservations dépassées
│       ├── GenerateNotifications.php       # Générer les notifications
│       └── RecalculateCustomerScores.php   # Recalculer les scores clients
├── Enums/                          # Enums PHP 8.1+
│   ├── ActivityAction.php
│   ├── PaymentMethod.php
│   ├── PaymentStatus.php
│   ├── SaleStatus.php
│   └── SaleType.php
├── Helpers/
│   ├── ActivityLogger.php          # Helper de logging d'activité
│   ├── FrontendRoutes.php          # Helper pour URLs frontend
│   └── PostgresTreasuryHelper.php  # Helper SQL pour trésorerie
├── Http/
│   ├── Controllers/                # 36 controllers (voir section dédiée)
│   ├── Middleware/                  # 7 middleware (voir section dédiée)
│   ├── Requests/                   # 31 Form Requests (validation)
│   └── Resources/                  # 48 API Resources (transformation JSON)
├── Models/                         # Modèles Eloquent
├── Observers/
│   └── CompanyInfoObserver.php     # Observer sur les infos entreprise
├── Policies/
│   ├── CurrencyRatePolicy.php
│   ├── NotificationPolicy.php
│   └── NotificationPreferencePolicy.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── AuthServiceProvider.php
├── Services/                       # 8 services métier (voir section dédiée)
└── LogsActivity.php                # Trait pour le logging automatique des modèles
```

---

## Base de données

**Moteur par défaut** : SQLite (`database/database.sqlite`)

Support configuré pour : SQLite, MySQL, MariaDB, PostgreSQL, SQL Server.

### Modèle de données

Les migrations sont dans `database/migrations/`. Voici les entités principales :

| Domaine               | Tables                                                                          |
|-----------------------|---------------------------------------------------------------------------------|
| **Utilisateurs**      | `users`                                                                         |
| **Produits**          | `categories`, `products`, `product_variants`, `attribute_types`, `attribute_values`, `product_attributes`, `variant_attribute_values` |
| **Stock**             | `locations`, `product_variant_locations`, `stock_movements`, `stock_batches`, `sale_item_batches` |
| **Réapprovisionnement** | `stock_receipts`, `stock_receipt_items`, `stock_receipt_item_ratings`, `suppliers`, `freight_forwarders`, `coordinates` |
| **Ventes**            | `sales`, `sale_items`, `credits`, `credit_installments`, `installment_transactions`, `reservations`, `reservation_deposits` |
| **Clients**           | `customers`                                                                     |
| **Trésorerie**        | `accounts`, `account_types`, `account_transactions`, `transaction_types`, `currency_rates` |
| **Dépenses**          | `expense_categories`, `planned_expenses`                                        |
| **Comptage caisse**   | `cash_counts`, `cash_count_denominations`                                       |
| **Notifications**     | `notifications`, `notification_preferences`                                     |
| **Activité**          | `activity_logs`                                                                 |
| **Entreprise**        | `company_infos`                                                                 |

---

## Authentification et autorisation

- **Laravel Sanctum** : authentification par token Bearer
- **Flux** : `POST /api/auth/login` retourne `{ token, user }`, le token est envoyé dans le header `Authorization: Bearer <token>`
- **Rôles** : `admin`, `vendeur` (champ `role` dans la table `users`)
- **Middleware `role:admin`** : restreint certaines routes aux administrateurs
- **Middleware `admin`** : alias pour la vérification admin sur les notifications

---

## Controllers

36 controllers dans `app/Http/Controllers/` :

| Controller                        | Domaine                           | Méthodes principales                                            |
|-----------------------------------|-----------------------------------|-----------------------------------------------------------------|
| `AuthController`                  | Authentification                  | `login`, `register`, `me`, `logout`                             |
| `UserController`                  | Utilisateurs                      | `index`, `indexUsers`, `store`, `update`, `toggleStatus`        |
| `CategoryController`              | Catégories produits               | CRUD + `withProducts`, `forSale`                                |
| `ProductController`               | Produits                          | CRUD + `getProductsForSale`, `showWithVariants`, `statistics`, `updateBasePrices`, `getProductAttributeTypes`, `getVariantBatches`, `getAvailableAttributes` |
| `ProductVariantController`        | Variantes produit                 | CRUD (nested sous product)                                      |
| `AttributeTypeController`         | Types d'attributs                 | CRUD                                                            |
| `AttributeValueController`        | Valeurs d'attributs               | CRUD + `reorder`                                                |
| `SupplierController`              | Fournisseurs                      | CRUD + `statistics`, `getStockReceipts`                         |
| `FreightForwarderController`      | Transitaires                      | CRUD + `statistics`, `getStockReceipts`                         |
| `CoordinateController`            | Coordonnées géographiques         | CRUD + `countries`, `citiesByCountry`, `usage`                  |
| `CurrencyRateController`          | Taux de change                    | CRUD + `current`, `atDate`, `convert`, `checkFreshness`         |
| `AccountTypeController`           | Types de comptes                  | CRUD                                                            |
| `AccountController`               | Comptes monétaires                | CRUD (sauf delete) + `getCashMobileMoney`, `activate`, `deactivate`, `stats`, `balanceAtDate` |
| `AccountTransactionController`    | Transactions financières          | `index`, `show`, `store`, `transfer`, `cancel`, `storeOperationalExpense`, `indexExpenseOperation` |
| `TransactionTypeController`       | Types de transactions             | CRUD                                                            |
| `ExpenseCategoryController`       | Catégories de dépenses            | CRUD + `toggleStatus`                                           |
| `TreasuryController`              | Vue globale trésorerie            | `overview`, `withCurrencies`, `stats`                           |
| `StockReceiptController`          | Réceptions de stock               | CRUD + workflow complet (mark shipped/in-transit/arrived/rated) + `validateReceipt`, `cancel`, `getCostRecommendations`, `applyCosts`, `addExpense`, `getExpenses`, `moveReceivedQuantity`, `recordPayment`, `rateReceipt`, `getCostAllocated`, `statistics`, `globalStatistics` |
| `StockPaymentController`          | Paiements réapprovisionnement     | `paySupplier`, `payFreight`, `payComplete`, `getReceiptTransactions` |
| `LocationController`              | Emplacements de stockage          | CRUD + `canDelete`, `getVariantsDetail`, `getStockMovements`, `statistics`, `getActiveReservations`, `activeLocations`, `warehouses` |
| `StockMovementController`         | Mouvements de stock               | `index`, `show`, `statistics`, `grouped`, `batchDetails`, `transfer`, `bulkTransfer`, `adjustment`, `bulkAdjustment`, `declareLoss`, `reconcileInventory`, `variantHistory`, `locationHistory` |
| `ProductVariantLocationController`| Localisation des variantes        | CRUD + `canDelete`, `getByLocation`, `locationStatistics`, `getByVariant` |
| `CustomerController`              | Clients                           | CRUD + `searchForSale`, `statistics`, `adjustScore`, `recalculateScore`, `addLoyaltyPoints`, `useLoyaltyPoints`, `adjustCreditLimit`, `canGetCredit`, historique par type |
| `SaleController`                  | Ventes (tous types)               | `storeImmediate`, `storeCredit`, `storeReservation`, `cancelImmediateSale`, listing et détail par type, `payInstallment`, `cancelCredit`, `completeReservation`, `cancelReservation`, `addDeposit` |
| `DashboardController`             | Tableau de bord                   | `index`                                                         |
| `SalesStatisticsController`       | Statistiques ventes + finances    | `overview`, `timeline`, `topProducts`, `byCategory`, `bySeller`, `byPaymentMethod`, `discounts`, `credits`, `reservations`, `financialDashboard`, `financialTimeline`, `profitsOverview`, `expensesOverview`, `lossesOverview` |
| `PlannedExpenseController`        | Dépenses planifiées               | CRUD + `stats`, `markPaid`, `transactions`                      |
| `InvoiceController`               | Factures PDF                      | `downloadSale`, `downloadCredit`, `downloadPaymentReceipt`, `downloadReservation`, `downloadReservationReceipt`, `downloadCashCount` |
| `PrintController`                 | Impression thermique POS          | `testPrinter`, `printSale`, `printCredit`, `printReservation`, `printReservationReceipt`, `printCashCount`, `printInstallmentTransaction` |
| `CashCountController`             | Comptage de caisse                | `index`, `store`, `show`, `update`                              |
| `NotificationController`          | Notifications                     | `index`, `count`, `markAsRead`, `dismiss`, `markAllAsRead`, `dismissByType`, `getPreferences`, `updatePreference`, `generate` |
| `CompanyInfoController`           | Infos entreprise                  | `index`, `update`                                               |
| `FileUploadController`            | Upload de fichiers                | `uploadImage`, `deleteImage`                                    |
| `SystemController`                | Informations système              | `getServerInfo`                                                 |
| `ActivityLogController`           | Journaux d'activité               | `index`, `show`, `statistics`, `failures`, `actionsByCategory`, `byModel`, suppression par dates/statut/ancienneté |

---

## Services métier

8 services dans `app/Services/` encapsulant la logique complexe :

| Service                  | Rôle                                                              |
|--------------------------|-------------------------------------------------------------------|
| `SaleService`            | Création de ventes (immédiate, crédit, réservation), annulations, paiements d'échéances, finalisation réservation |
| `StockService`           | Gestion du stock (mouvements, transferts, ajustements, pertes, réconciliation) |
| `InvoiceService`         | Génération de factures PDF (ventes, crédits) via DomPDF           |
| `CreditInvoiceService`   | Génération de factures PDF spécifiques aux crédits                |
| `PaymentReceiptService`  | Génération de reçus de paiement PDF                               |
| `PosPrintService`        | Impression directe sur imprimante thermique (ESC/POS via USB)     |
| `NotificationService`    | Logique de génération et gestion des notifications                |
| `AccountStatsService`    | Calculs statistiques pour les comptes monétaires                  |

---

## Modèles Eloquent

Principaux modèles dans `app/Models/` :

| Modèle                    | Table                        | Relations principales                              |
|---------------------------|------------------------------|---------------------------------------------------|
| `User`                    | `users`                      | hasMany: Sales, ActivityLogs                      |
| `Category`                | `categories`                 | hasMany: Products, self (sous-catégories)         |
| `Product`                 | `products`                   | belongsTo: Category, hasMany: ProductVariants, ProductAttributes |
| `ProductVariant`          | `product_variants`           | belongsTo: Product, hasMany: VariantAttributeValues, ProductVariantLocations, StockBatches |
| `AttributeType`           | `attribute_types`            | hasMany: AttributeValues                          |
| `AttributeValue`          | `attribute_values`           | belongsTo: AttributeType                          |
| `Location`                | `locations`                  | hasMany: ProductVariantLocations, StockMovements  |
| `ProductVariantLocation`  | `product_variant_locations`  | belongsTo: ProductVariant, Location               |
| `StockMovement`           | `stock_movements`            | belongsTo: ProductVariantLocation, User           |
| `StockBatch`              | `stock_batches`              | belongsTo: ProductVariant, StockReceiptItem       |
| `StockReceipt`            | `stock_receipts`             | belongsTo: Supplier, FreightForwarder, hasMany: StockReceiptItems |
| `StockReceiptItem`        | `stock_receipt_items`        | belongsTo: StockReceipt, ProductVariant           |
| `Supplier`                | `suppliers`                  | belongsTo: Coordinate, hasMany: StockReceipts     |
| `FreightForwarder`        | `freight_forwarders`         | belongsTo: Coordinate, hasMany: StockReceipts     |
| `Coordinate`              | `coordinates`                | hasMany: Suppliers, FreightForwarders              |
| `Customer`                | `customers`                  | hasMany: Sales                                    |
| `Sale`                    | `sales`                      | belongsTo: Customer, User, hasMany: SaleItems, hasOne: Credit, Reservation |
| `SaleItem`                | `sale_items`                 | belongsTo: Sale, ProductVariant, ProductVariantLocation |
| `Credit`                  | `credits`                    | belongsTo: Sale, hasMany: CreditInstallments      |
| `CreditInstallment`       | `credit_installments`        | belongsTo: Credit, hasMany: InstallmentTransactions |
| `Reservation`             | `reservations`               | belongsTo: Sale, hasMany: ReservationDeposits     |
| `Account`                 | `accounts`                   | belongsTo: AccountType, hasMany: AccountTransactions |
| `AccountTransaction`      | `account_transactions`       | belongsTo: Account, TransactionType               |
| `CurrencyRate`            | `currency_rates`             | Taux de change (EUR, USD, CNY vers MGA)           |
| `PlannedExpense`          | `planned_expenses`           | belongsTo: ExpenseCategory                        |
| `CashCount`               | `cash_counts`                | hasMany: CashCountDenominations, belongsTo: User  |
| `Notification`            | `notifications`              | belongsTo: User                                   |
| `ActivityLog`             | `activity_logs`              | belongsTo: User, morphTo: model                   |
| `CompanyInfo`             | `company_infos`              | Singleton (1 ligne)                               |

---

## Enums

| Enum              | Valeurs                                                                 |
|-------------------|-------------------------------------------------------------------------|
| `SaleType`        | `immediate`, `credit`, `reservation`                                    |
| `SaleStatus`      | `completed`, `pending`, `cancelled`, `partial_paid`, `overdue`, `expired` |
| `PaymentStatus`   | `pending`, `partial`, `paid`, `overdue`, `cancelled`                    |
| `PaymentMethod`   | `cash`, `mobile_money`, `bank_transfer`, `check`                        |
| `ActivityAction`  | Actions loguées (création, modification, suppression, paiement, etc.)   |

---

## Form Requests

31 Form Requests dans `app/Http/Requests/` pour la validation :

| Request                          | Usage                                     |
|----------------------------------|-------------------------------------------|
| `StoreImmediateSaleRequest`      | Validation vente immédiate                |
| `StoreCreditSaleRequest`         | Validation vente à crédit                 |
| `StoreReservationRequest`        | Validation création réservation           |
| `CompleteReservationRequest`     | Validation finalisation réservation       |
| `PayCreditInstallmentRequest`    | Validation paiement échéance              |
| `StoreStockReceiptRequest`       | Validation création réception stock       |
| `UpdateStockReceiptRequest`      | Validation modification réception         |
| `RateStockReceiptRequest`        | Validation évaluation qualité             |
| `MarkAsShippedRequest`           | Validation passage en "envoyé"            |
| `MarkAsArrivedRequest`           | Validation passage en "arrivé"            |
| `MarkAsValidateRequest`          | Validation finale d'une réception         |
| `ApplyCostAllocationRequest`     | Validation répartition des coûts          |
| `GetCostRecommendationsRequest`  | Validation demande recommandations coûts  |
| `AddExpenseRequest`              | Validation ajout dépense annexe           |
| `PaySupplierRequest`             | Validation paiement fournisseur           |
| `PayFreightRequest`              | Validation paiement transitaire           |
| `PayCompleteStockRequest`        | Validation paiement complet               |
| `StoreAccountRequest`            | Validation création compte                |
| `UpdateAccountRequest`           | Validation modification compte            |
| `StoreAccountTypeRequest`        | Validation type de compte                 |
| `UpdateAccountTypeRequest`       | Validation modification type compte       |
| `StoreTransactionRequest`        | Validation transaction financière         |
| `StoreTransferRequest`           | Validation transfert entre comptes        |
| `StoreOperationalExpenseRequest` | Validation dépense opérationnelle         |
| `StoreCurrencyRateRequest`       | Validation taux de change                 |
| `UpdateCurrencyRateRequest`      | Validation modification taux              |
| `StoreCustomerRequest`           | Validation création client                |
| `UpdateCustomerRequest`          | Validation modification client            |
| `StoreExpenseCategoryRequest`    | Validation catégorie de dépense           |
| `UpdateExpenseCategoryRequest`   | Validation modification catégorie         |
| `StoreTransactionTypeRequest`    | Validation type de transaction            |
| `UpdateTransactionTypeRequest`   | Validation modification type transaction  |

---

## Resources API

48 API Resources dans `app/Http/Resources/` transforment les modèles Eloquent en JSON structuré pour le frontend.

Principales :
- `ProductResource`, `ProductCollection` — Produits avec variantes, attributs, stock
- `SaleResource`, `SaleItemResource` — Ventes avec items et détails paiement
- `CreditResource`, `CreditListResource`, `CreditInstallmentResource` — Crédits et échéances
- `ReservationResource`, `ReservationListResource` — Réservations
- `StockReceiptResource`, `StockReceiptCollection` — Réceptions avec items et ratings
- `AccountResource`, `AccountCollection`, `AccountStatsResource` — Comptes
- `TransactionResource`, `TransactionCollection` — Transactions
- `CustomerResource`, `CustomerCollection` — Clients
- `CurrencyRateResource`, `CurrencyRateCollection` — Taux de change
- `ImmediateSaleListResource`, `ImmediateSaleDetailResource` — Ventes immédiates
- `CostRecommendationsSummaryResource`, `ProductCostRecommendationResource` — Répartition coûts

---

## Middleware

| Middleware                         | Fichier                                | Rôle                                             |
|------------------------------------|----------------------------------------|--------------------------------------------------|
| `CheckRole`                        | `CheckRole.php`                        | Vérifie le rôle utilisateur (`role:admin`)       |
| `IsAdmin`                          | `IsAdmin.php`                          | Middleware alias `admin`                         |
| `SetDynamicUrl`                    | `SetDynamicUrl.php`                    | Configure l'URL du serveur dynamiquement         |
| `TrustProxies`                     | `TrustProxies.php`                     | Gestion des proxies (Docker, Nginx)              |
| `ExpireReservationsMiddleware`     | `ExpireReservationsMiddleware.php`     | Expire les réservations dépassées à chaque requête |
| `GenerateNotificationsMiddleware`  | `GenerateNotificationsMiddleware.php`  | Génère les notifications périodiquement          |
| `AdminNotificationsMiddleware`     | `AdminNotificationsMiddleware.php`     | Notifications spécifiques admin                  |

---

## Commandes Artisan

| Commande                         | Description                                            |
|----------------------------------|--------------------------------------------------------|
| `tasks:catchup --days=N`        | Rattraper les tâches manquées des N derniers jours     |
| `reservations:expire`           | Expirer les réservations dépassées                     |
| `notifications:generate`        | Générer les notifications (stock bas, crédits en retard...) |
| `customers:recalculate-scores`  | Recalculer les scores de fiabilité clients             |

---

## Tâches planifiées

Définies dans `app/Console/Kernel.php` :

| Planification      | Tâche                                   | Description                          |
|--------------------|-----------------------------------------|--------------------------------------|
| Au démarrage       | `tasks:catchup --days=2`                | Rattraper les tâches manquées        |
| Toutes les heures  | `notifications:generate`                | Générer les notifications            |
| Quotidien (02h00)  | `tasks:cleanup`                         | Nettoyer les anciennes notifications |
| Toutes les 6h      | Closure : expirer réservations          | Mettre à jour le statut              |
| Quotidien (01h00)  | Closure : crédits en retard             | Marquer les crédits overdue          |
| Toutes les 3h      | `tasks:safety-check`                    | Vérification de sécurité             |

---

## Observers et Traits

### Observer
- `CompanyInfoObserver` — Déclenché lors de la modification des infos entreprise

### Trait
- `LogsActivity` (`app/LogsActivity.php`) — Trait Eloquent pour le logging automatique. Intercepte les événements `created`, `updated`, `deleted` et crée un `ActivityLog` avec les valeurs avant/après, l'utilisateur, l'IP et le user-agent.

---

## Helpers

| Helper                      | Rôle                                                    |
|-----------------------------|---------------------------------------------------------|
| `ActivityLogger`            | Logging structuré des actions métier                    |
| `FrontendRoutes`            | Construction d'URLs frontend (pour les notifications)   |
| `PostgresTreasuryHelper`    | Requêtes SQL spécifiques PostgreSQL pour la trésorerie  |

---

## Seeders

| Seeder                        | Description                                     |
|-------------------------------|-------------------------------------------------|
| `DatabaseSeeder`              | Seeder principal (appelle les autres)           |
| `TreasurySeeder`              | Crée les types de comptes et comptes de base    |
| `CurrencyRateSeeder`          | Insère les taux de change initiaux              |
| `ReversalTransactionTypeSeeder` | Crée le type de transaction "annulation"      |

---

## Configuration

Fichiers de configuration notables dans `config/` :

| Fichier          | Description                                              |
|------------------|----------------------------------------------------------|
| `database.php`   | Configuration multi-DB (SQLite par défaut)               |
| `sanctum.php`    | Configuration tokens Sanctum                             |
| `dompdf.php`     | Configuration génération PDF (DomPDF)                    |
| `snappy.php`     | Configuration alternative PDF (wkhtmltopdf)              |
| `printing.php`   | Config imprimante POS (`/dev/usb/lp0`, largeur 42 chars) |
| `notification.php` | Configuration du système de notifications              |
| `filesystems.php` | Configuration stockage fichiers (images produits)       |

---

## Génération PDF et impression

### PDF (DomPDF)
Les factures sont générées via **DomPDF** (`barryvdh/laravel-dompdf`). Templates Blade dans `resources/views/` :
- Factures de vente
- Factures de crédit
- Reçus de paiement d'échéance
- Documents de réservation
- Reçus de finalisation réservation
- Rapports de comptage de caisse

### Impression thermique (ESC/POS)
Impression directe via **mike42/escpos-php** sur imprimante USB (`/dev/usb/lp0`).
Le `PosPrintService` génère des tickets formatés (42 chars de large) pour :
- Tickets de vente
- Tickets de crédit
- Tickets de réservation
- Reçus de paiement
- Rapports de comptage

---

## Notifications

Le système de notifications génère automatiquement des alertes pour :
- **Stock bas** — Variantes dont le stock est inférieur au seuil
- **Crédits en retard** — Échéances dépassées non payées
- **Réservations expirant bientôt** — Réservations proches de la date limite
- **Réceptions en attente** — Commandes fournisseur non livrées

Les notifications sont :
- Générées via la commande `notifications:generate` (toutes les heures)
- Consultables via l'API (`GET /api/notifications`)
- Marquables comme lues / supprimables
- Configurables par utilisateur (`notification_preferences`)

---

## Routes API

Toutes les routes sont documentées en détail dans `routes/api.php` avec des commentaires sur chaque endpoint.

Chaque route est annotée avec :
- Sa méthode HTTP et son URL complète
- Une description de ce qu'elle fait
- `[UTILISÉ]` ou `[NON UTILISÉ]` selon si le frontend la consomme

### Résumé par domaine

| Préfixe API                     | Nb routes | Controller                     |
|---------------------------------|-----------|--------------------------------|
| `/api/auth`                     | 4         | AuthController                 |
| `/api/users`                    | 4         | UserController                 |
| `/api/categories`               | 7         | CategoryController             |
| `/api/products`                 | 12        | ProductController              |
| `/api/products/.../variants`    | 5         | ProductVariantController       |
| `/api/attribute-types`          | 11        | AttributeTypeController + AttributeValueController |
| `/api/suppliers`                | 4         | SupplierController             |
| `/api/freight-forwarders`       | 4         | FreightForwarderController     |
| `/api/coordinates`              | 7         | CoordinateController           |
| `/api/currency-rates`           | 7         | CurrencyRateController         |
| `/api/account-types`            | 5         | AccountTypeController          |
| `/api/accounts`                 | 8         | AccountController              |
| `/api/transactions`             | 6         | AccountTransactionController   |
| `/api/treasury`                 | 3         | TreasuryController             |
| `/api/stock-payments`           | 4         | StockPaymentController         |
| `/api/locations`                | 12        | LocationController             |
| `/api/stock-receipts`           | 18        | StockReceiptController         |
| `/api/stock-movements`          | 14        | StockMovementController        |
| `/api/customers`                | 15        | CustomerController             |
| `/api/product-variant-locations`| 9         | ProductVariantLocationController |
| `/api/sales`                    | 9         | SaleController                 |
| `/api/credits`                  | 6         | SaleController                 |
| `/api/reservations`             | 7         | SaleController                 |
| `/api/planned-expenses`         | 8         | PlannedExpenseController       |
| `/api/statistics/sales`         | 9         | SalesStatisticsController      |
| `/api/statistics/financial`     | 5         | SalesStatisticsController      |
| `/api/dashboard`                | 1         | DashboardController            |
| `/api/invoices`                 | 6         | InvoiceController              |
| `/api/cash-counts`              | 4         | CashCountController            |
| `/api/company-info`             | 2         | CompanyInfoController          |
| `/api/print`                    | 7         | PrintController                |
| `/api/activity-logs`            | 10        | ActivityLogController          |
| `/api/notifications`            | 9         | NotificationController         |
| `/api/system`                   | 1         | SystemController               |

---

## Dépendances

### Production

| Package                    | Version | Usage                                     |
|----------------------------|---------|-------------------------------------------|
| `laravel/framework`        | ^12.0   | Framework PHP                             |
| `laravel/sanctum`          | ^4.2    | Authentification par token                |
| `barryvdh/laravel-dompdf`  | ^3.1    | Génération de PDF (factures)              |
| `barryvdh/laravel-snappy`  | ^1.0    | Génération PDF alternative (wkhtmltopdf)  |
| `mike42/escpos-php`        | ^4.0    | Impression thermique POS (ESC/POS USB)    |
| `laravel/tinker`           | ^2.10   | Console interactive                       |

### Développement

| Package                    | Usage                           |
|----------------------------|---------------------------------|
| `fakerphp/faker`           | Données factices pour les tests |
| `laravel/pail`             | Logs en temps réel              |
| `laravel/pint`             | Formateur de code PHP           |
| `laravel/sail`             | Environnement Docker            |
| `mockery/mockery`          | Mocking pour les tests          |
| `phpunit/phpunit`          | Tests unitaires et fonctionnels |
| `nunomaduro/collision`     | Meilleur affichage des erreurs  |

---

## Docker et déploiement

Un `Dockerfile` est fourni pour le backend :

```bash
docker build -t express-sale-backend .
docker run -p 8000:8000 express-sale-backend
```

Un `docker-compose.yml` est disponible à la racine du projet pour orchestrer frontend + backend.

Le fichier `start.sh` détecte automatiquement l'IP du serveur et démarre Laravel sur `0.0.0.0:8000`.

---

## Notes pour les devs

### Fichiers nettoyés lors de l'audit
- `test-thermal.php` — Script de test imprimante (supprimé)
- `test.html` — Page HTML orpheline (supprimé)

### Routes backend non consommées par le frontend
Les routes marquées `[NON UTILISÉ]` dans `api.php` sont fonctionnelles mais aucun service frontend ne les appelle. Elles sont prêtes pour un usage futur :
- `POST /auth/register`, `GET /auth/me`
- `GET /categories/with-products`
- `GET /currency-rates/at-date`, `GET /currency-rates/check-freshness`
- `PATCH /accounts/{id}/activate|deactivate`, `GET /accounts/{id}/stats|balance-at-date`
- CRUD `/transaction-types`
- `PATCH /expense-categories/{id}/toggle-status`
- Tout `/treasury/*`
- `GET /sales/statistics`, `GET /sales` (générique)
- Gestion client avancée : `statistics`, `adjust-score`, `recalculate-score`, `add/use-loyalty-points`, `adjust-credit-limit`, `can-get-credit`
- `GET /credits/overdue|due-soon`
- `GET /reservations/expiring-soon|expired`, `POST /reservations/{id}/add-deposit`
- `GET /system/info`
- `GET|POST /stock-receipts/{id}/expenses`
- `GET /variants/{id}/movements`, `GET /locations/{id}/movements`

### Conventions
- **Controllers** : un controller par domaine, méthodes RESTful
- **Validation** : via Form Requests (jamais dans les controllers)
- **Transformation** : via API Resources (jamais de `toArray()` brut)
- **Logique métier complexe** : dans les Services (`app/Services/`)
- **Enums** : PHP 8.1+ backed enums pour les statuts et types
- **Trait LogsActivity** : à ajouter sur les modèles pour le logging auto
