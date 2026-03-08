<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\AttributeTypeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\FreightForwarderController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\CoordinateController;
use App\Http\Controllers\CurrencyRateController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\StockPaymentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\ProductVariantLocationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesStatisticsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CashCountController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PlannedExpenseController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiProviderConfigController;
use App\Http\Controllers\AiTaskController;
use App\Models\AiProviderConfig;

/*
|==========================================================================
| API Routes — Express Sale
|==========================================================================
|
| Toutes les routes sont préfixées par /api (défini dans RouteServiceProvider).
| Les routes protégées nécessitent un token Bearer (Laravel Sanctum).
| Middleware 'role:admin' = réservé aux administrateurs.
|
| LÉGENDE :
|   [UTILISÉ]     = Appelé par le frontend
|   [NON UTILISÉ] = Route existante, pas encore consommée par le frontend
|
*/

// ============================================================================
// HEALTH CHECK — Vérifier que l'API répond
// ============================================================================
// GET /api/ping → { "status": "ok" }
Route::get('/ping', fn () => response()->json(['status' => 'ok']));

// ============================================================================
// AUTHENTIFICATION (préfixe: /api/auth)
// ============================================================================
Route::prefix('auth')->group(function () {

    // --- Routes publiques (pas de token requis) ---

    // POST /api/auth/login → Connexion, retourne { token, user } [UTILISÉ]
    Route::post('/login', [AuthController::class, 'login']);

    // POST /api/auth/register → Inscription d'un nouvel utilisateur [NON UTILISÉ]
    Route::post('/register', [AuthController::class, 'register']);

    // --- Routes protégées (token Sanctum requis) ---
    Route::middleware('auth:sanctum')->group(function () {
        // GET /api/auth/me → Infos de l'utilisateur connecté [NON UTILISÉ]
        Route::get('/me', [AuthController::class, 'me']);

        // POST /api/auth/logout → Déconnexion (révoque le token) [UTILISÉ]
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ============================================================================
// ROUTES PROTÉGÉES (token Sanctum requis pour tout ce qui suit)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {

    // ========================================================================
    // UTILISATEURS (préfixe: /api/users)
    // ========================================================================

    // GET /api/users/all → Liste TOUS les utilisateurs (sans pagination) [UTILISÉ]
    Route::get('/users/all', [UserController::class, 'indexUsers']);

    // PATCH /api/users/{id}/toggle → Active/désactive un utilisateur [UTILISÉ]
    Route::patch('/users/{id}/toggle', [UserController::class, 'toggleStatus']);

    // GET    /api/users      → Liste paginée des utilisateurs [UTILISÉ]
    // POST   /api/users      → Créer un utilisateur [UTILISÉ]
    // PUT    /api/users/{id} → Modifier un utilisateur [UTILISÉ]
    Route::apiResource("users", UserController::class)->only(['index', 'store', 'update']);

    // ========================================================================
    // CATÉGORIES (préfixe: /api/categories)
    // ========================================================================

    // GET /api/categories/with-products → Catégories avec leurs produits [NON UTILISÉ]
    Route::get('categories/with-products', [CategoryController::class, 'withProducts']);

    // GET /api/categories/for-sale → Arbre catégories pour l'interface de vente [UTILISÉ]
    Route::get('categories/for-sale', [CategoryController::class, 'forSale']);

    // GET    /api/categories      → Liste toutes les catégories (filtres: only_roots, parent_id) [UTILISÉ]
    // POST   /api/categories      → Créer une catégorie [UTILISÉ]
    // GET    /api/categories/{id} → Détail d'une catégorie [UTILISÉ]
    // PUT    /api/categories/{id} → Modifier une catégorie [UTILISÉ]
    // DELETE /api/categories/{id} → Supprimer une catégorie [UTILISÉ]
    Route::apiResource('categories', CategoryController::class);

    // ========================================================================
    // PRODUITS (préfixe: /api/products)
    // ========================================================================

    // GET /api/products/for-sale → Produits pour l'interface POS (avec variantes, stock, prix) [UTILISÉ]
    Route::get('products/for-sale', [ProductController::class, 'getProductsForSale']);

    // GET /api/products/attributes/available → Types d'attributs disponibles pour filtrer en vente [UTILISÉ]
    Route::get('/products/attributes/available', [ProductController::class, 'getAvailableAttributes']);

    // GET    /api/products      → Liste paginée des produits (filtres: search, category_id...) [UTILISÉ]
    // POST   /api/products      → Créer un produit [UTILISÉ]
    // GET    /api/products/{id} → Détail d'un produit [UTILISÉ]
    // PUT    /api/products/{id} → Modifier un produit [UTILISÉ]
    // DELETE /api/products/{id} → Supprimer un produit [UTILISÉ]
    Route::apiResource('products', ProductController::class);

    // GET /api/products/with-variants/{id} → Produit avec toutes ses variantes chargées [UTILISÉ]
    Route::get('products/with-variants/{id}', [ProductController::class, 'showWithVariants']);

    // GET /api/products/{id}/statistics → Statistiques de vente d'un produit [UTILISÉ]
    Route::get('products/{id}/statistics', [ProductController::class, 'statistics']);

    // POST /api/products/update-base-prices → Mise à jour en masse des prix de base [UTILISÉ]
    Route::post('products/update-base-prices', [ProductController::class, 'updateBasePrices']);

    // GET /api/products/{product}/attribute-types → Types d'attributs utilisés par ce produit [UTILISÉ]
    Route::get('products/{product}/attribute-types', [ProductController::class, 'getProductAttributeTypes']);

    // GET /api/products/{productId}/variants/{variantId}/batches → Lots (batches) d'une variante [UTILISÉ]
    Route::get('products/{productId}/variants/{variantId}/batches', [ProductController::class, 'getVariantBatches']);

    // ========================================================================
    // VARIANTES PRODUIT (préfixe: /api/products/{productId}/variants)
    // ========================================================================

    // GET    .../variants          → Liste variantes d'un produit [UTILISÉ]
    Route::get('products/{productId}/variants', [ProductVariantController::class, 'index']);
    // POST   .../variants          → Créer une variante [UTILISÉ]
    Route::post('products/{productId}/variants', [ProductVariantController::class, 'store']);
    // PUT    .../variants/{id}     → Modifier une variante [UTILISÉ]
    Route::put('products/{productId}/variants/{id}', [ProductVariantController::class, 'update']);
    // DELETE .../variants/{id}     → Supprimer une variante [UTILISÉ]
    Route::delete('products/{productId}/variants/{id}', [ProductVariantController::class, 'destroy']);
    // GET    .../variants/{id}     → Détail d'une variante [UTILISÉ]
    Route::get('products/{productId}/variants/{id}', [ProductVariantController::class, 'show']);

    // ========================================================================
    // VALEURS D'ATTRIBUTS (préfixe: /api/attribute-types/{attributeType}/values)
    // ========================================================================
    Route::prefix('attribute-types/{attributeType}')->group(function () {
        // GET    .../values              → Liste des valeurs d'un type d'attribut [UTILISÉ]
        Route::get('values', [AttributeValueController::class, 'index']);
        // POST   .../values              → Créer une valeur [UTILISÉ]
        Route::post('values', [AttributeValueController::class, 'store']);
        // GET    .../values/{value}      → Détail d'une valeur [UTILISÉ]
        Route::get('values/{attributeValue}', [AttributeValueController::class, 'show']);
        // PUT    .../values/{value}      → Modifier une valeur [UTILISÉ]
        Route::put('values/{attributeValue}', [AttributeValueController::class, 'update']);
        // DELETE .../values/{value}      → Supprimer une valeur [UTILISÉ]
        Route::delete('values/{attributeValue}', [AttributeValueController::class, 'destroy']);
        // POST   .../values/reorder      → Réordonner les valeurs [UTILISÉ]
        Route::post('values/reorder', [AttributeValueController::class, 'reorder']);
    });

    // ========================================================================
    // TYPES D'ATTRIBUTS (préfixe: /api/attribute-types)
    // ========================================================================

    // GET    /api/attribute-types      → Liste tous les types d'attributs (Taille, Couleur...) [UTILISÉ]
    // POST   /api/attribute-types      → Créer un type d'attribut [UTILISÉ]
    // GET    /api/attribute-types/{id} → Détail [UTILISÉ]
    // PUT    /api/attribute-types/{id} → Modifier [UTILISÉ]
    // DELETE /api/attribute-types/{id} → Supprimer [UTILISÉ]
    Route::apiResource('attribute-types', AttributeTypeController::class);

    // ========================================================================
    // TRANSITAIRES (préfixe: /api/freight-forwarders)
    // ========================================================================

    // CRUD complet : GET (liste), POST, GET/{id}, PUT/{id}, DELETE/{id} [UTILISÉ]
    Route::apiResource('freight-forwarders', FreightForwarderController::class);

    // GET /api/freight-forwarders/{id}/statistics → Stats d'un transitaire [UTILISÉ]
    Route::get('freight-forwarders/{id}/statistics', [FreightForwarderController::class, 'statistics']);

    // GET /api/freight-forwarders/{id}/stock-receipts → Réceptions liées à ce transitaire [UTILISÉ]
    Route::get('/freight-forwarders/{id}/stock-receipts', [FreightForwarderController::class, 'getStockReceipts']);

    // ========================================================================
    // FOURNISSEURS (préfixe: /api/suppliers)
    // ========================================================================

    // GET /api/suppliers/{id}/statistics → Stats d'un fournisseur [UTILISÉ]
    Route::get('suppliers/{id}/statistics', [SupplierController::class, 'statistics']);

    // CRUD complet : GET (liste), POST, GET/{id}, PUT/{id}, DELETE/{id} [UTILISÉ]
    Route::apiResource('suppliers', SupplierController::class);

    // GET /api/suppliers/{id}/stock-receipts → Réceptions liées à ce fournisseur [UTILISÉ]
    Route::get('suppliers/{id}/stock-receipts', [SupplierController::class, 'getStockReceipts']);

    // ========================================================================
    // COORDONNÉES GÉOGRAPHIQUES (préfixe: /api/coordinates)
    // ========================================================================

    // GET /api/coordinates/countries → Liste des pays distincts [UTILISÉ]
    Route::get('coordinates/countries', [CoordinateController::class, 'countries']);

    // GET /api/coordinates/cities/{country} → Villes d'un pays [UTILISÉ]
    Route::get('coordinates/cities/{country}', [CoordinateController::class, 'citiesByCountry']);

    // GET /api/coordinates/{id}/usage → Où cette coordonnée est utilisée (fournisseurs, transitaires) [UTILISÉ]
    Route::get('coordinates/{id}/usage', [CoordinateController::class, 'usage']);

    // CRUD complet [UTILISÉ]
    Route::apiResource('coordinates', CoordinateController::class);

    // ========================================================================
    // UPLOAD DE FICHIERS
    // ========================================================================

    // POST   /api/upload-image → Upload d'une image (retourne le path) [UTILISÉ]
    Route::post('/upload-image', [FileUploadController::class, 'uploadImage']);

    // DELETE /api/delete-image → Supprime une image par son path [UTILISÉ]
    Route::delete('/delete-image', [FileUploadController::class, 'deleteImage']);

    // ========================================================================
    // TAUX DE CHANGE (préfixe: /api/currency-rates)
    // ========================================================================
    Route::prefix('currency-rates')->group(function () {
        // GET /api/currency-rates/current → Taux de change actuel [UTILISÉ]
        Route::get('current', [CurrencyRateController::class, 'current']);

        // GET /api/currency-rates/at-date → Taux à une date donnée [NON UTILISÉ]
        Route::get('at-date', [CurrencyRateController::class, 'atDate']);

        // POST /api/currency-rates/convert → Convertir un montant entre devises [UTILISÉ]
        Route::post('convert', [CurrencyRateController::class, 'convert']);

        // GET /api/currency-rates/check-freshness → Vérifier si le taux est à jour [NON UTILISÉ]
        Route::get('check-freshness', [CurrencyRateController::class, 'checkFreshness']);
    });

    // GET    /api/currency-rates      → Historique de tous les taux [UTILISÉ]
    // POST   /api/currency-rates      → Créer un nouveau taux [UTILISÉ]
    // PUT    /api/currency-rates/{id} → Modifier un taux [UTILISÉ]
    Route::apiResource('currency-rates', CurrencyRateController::class);

    // ========================================================================
    // TYPES DE COMPTES (préfixe: /api/account-types)
    // ========================================================================

    // GET /api/account-types → Liste des types de comptes (Caisse, Banque, Mobile Money...) [UTILISÉ]
    Route::apiResource('account-types', AccountTypeController::class);

    // ========================================================================
    // TYPES DE TRANSACTIONS (préfixe: /api/transaction-types) [NON UTILISÉ]
    // ========================================================================
    Route::apiResource('transaction-types', TransactionTypeController::class);

    // ========================================================================
    // CATÉGORIES DE DÉPENSES (préfixe: /api/expense-categories)
    // ========================================================================

    // GET  /api/expense-categories → Liste des catégories de dépenses [UTILISÉ]
    // POST /api/expense-categories → Créer une catégorie de dépense [UTILISÉ]
    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // PATCH /api/expense-categories/{id}/toggle-status → Activer/désactiver [NON UTILISÉ]
    Route::patch('expense-categories/{expenseCategory}/toggle-status',
        [ExpenseCategoryController::class, 'toggleStatus']);

    // ========================================================================
    // COMPTES MONÉTAIRES (préfixe: /api/accounts)
    // ========================================================================

    // GET /api/accounts/cash-mobile-money → Comptes cash + mobile money (pour paiements) [UTILISÉ]
    Route::get('accounts/cash-mobile-money', [AccountController::class, 'getCashMobileMoney']);

    // GET  /api/accounts      → Liste de tous les comptes [UTILISÉ]
    // POST /api/accounts      → Créer un compte [UTILISÉ]
    // GET  /api/accounts/{id} → Détail d'un compte [UTILISÉ]
    // PUT  /api/accounts/{id} → Modifier un compte [UTILISÉ]
    Route::apiResource('accounts', AccountController::class)->except(['destroy']);

    // GET /api/accounts/{account}/transactions → Historique des transactions d'un compte [UTILISÉ]
    Route::get('/accounts/{account}/transactions', [AccountTransactionController::class, 'index']);

    // PATCH /api/accounts/{id}/activate → Activer un compte [NON UTILISÉ]
    Route::patch('accounts/{account}/activate', [AccountController::class, 'activate']);

    // PATCH /api/accounts/{id}/deactivate → Désactiver un compte [NON UTILISÉ]
    Route::patch('accounts/{account}/deactivate', [AccountController::class, 'deactivate']);

    // GET /api/accounts/{id}/stats → Statistiques d'un compte [NON UTILISÉ]
    Route::get('accounts/{account}/stats', [AccountController::class, 'stats']);

    // GET /api/accounts/{id}/balance-at-date → Solde à une date donnée [NON UTILISÉ]
    Route::get('accounts/{account}/balance-at-date', [AccountController::class, 'balanceAtDate']);

    // ========================================================================
    // TRANSACTIONS FINANCIÈRES (préfixe: /api/transactions)
    // ========================================================================

    // POST /api/transactions/expense-operational → Créer une dépense opérationnelle [UTILISÉ]
    Route::post('transactions/expense-operational',
        [AccountTransactionController::class, 'storeOperationalExpense']);

    // GET /api/transactions/expense-operational → Liste des dépenses opérationnelles [UTILISÉ]
    Route::get('transactions/expense-operational',
        [AccountTransactionController::class, 'indexExpenseOperation']);

    // GET  /api/transactions/{id} → Détail d'une transaction [UTILISÉ]
    // POST /api/transactions      → Créer une transaction manuelle [UTILISÉ]
    Route::apiResource('transactions', AccountTransactionController::class)
        ->only(['show', 'store']);

    // POST /api/transactions/transfer → Transfert entre deux comptes [UTILISÉ]
    Route::post('transactions/transfer', [AccountTransactionController::class, 'transfer']);

    // POST /api/transactions/{id}/cancel → Annuler (crée écriture inverse) [UTILISÉ]
    Route::post('transactions/{transaction}/cancel', [AccountTransactionController::class, 'cancel']);

    // ========================================================================
    // TRÉSORERIE — VUE GLOBALE (préfixe: /api/treasury) [NON UTILISÉ]
    // ========================================================================
    Route::prefix('treasury')->group(function () {
        // GET /api/treasury/overview → Vue d'ensemble trésorerie [NON UTILISÉ]
        Route::get('overview', [TreasuryController::class, 'overview']);
        // GET /api/treasury/with-currencies → Soldes par devise [NON UTILISÉ]
        Route::get('with-currencies', [TreasuryController::class, 'withCurrencies']);
        // GET /api/treasury/stats → Statistiques trésorerie [NON UTILISÉ]
        Route::get('stats', [TreasuryController::class, 'stats']);
    });

    // ========================================================================
    // PAIEMENTS RÉAPPROVISIONNEMENT (préfixe: /api/stock-payments)
    // ========================================================================
    Route::prefix('stock-payments')->group(function () {
        // POST /api/stock-payments/supplier → Payer le fournisseur [UTILISÉ]
        Route::post('supplier', [StockPaymentController::class, 'paySupplier']);
        // POST /api/stock-payments/freight → Payer le transitaire [UTILISÉ]
        Route::post('freight', [StockPaymentController::class, 'payFreight']);
        // POST /api/stock-payments/complete → Paiement complet (fournisseur + transitaire) [UTILISÉ]
        Route::post('complete', [StockPaymentController::class, 'payComplete']);
        // GET /api/stock-payments/receipt/{id} → Transactions d'une réception [UTILISÉ]
        Route::get('receipt/{stock_receipt_id}', [StockPaymentController::class, 'getReceiptTransactions']);
    });

    // ========================================================================
    // EMPLACEMENTS DE STOCKAGE (préfixe: /api/locations)
    // ========================================================================

    // CRUD : GET (liste), POST, GET/{id}, PUT/{id}, DELETE/{id} [UTILISÉ]
    Route::apiResource('locations', LocationController::class);

    // GET /api/locations/{id}/can-delete → Vérifie si supprimable [UTILISÉ]
    Route::get('locations/{id}/can-delete', [LocationController::class, 'canDelete']);

    // GET /api/locations/{id}/variants-detail → Variantes dans cet emplacement (paginé) [UTILISÉ]
    Route::get('locations/{id}/variants-detail', [LocationController::class, 'getVariantsDetail']);

    // GET /api/locations/{id}/stock-movements → Mouvements de stock [UTILISÉ]
    Route::get('locations/{id}/stock-movements', [LocationController::class, 'getStockMovements']);

    // GET /api/locations/{id}/statistics → Statistiques d'un emplacement [UTILISÉ]
    Route::get('locations/{id}/statistics', [LocationController::class, 'statistics']);

    // GET /api/locations/{id}/reservations → Réservations actives dans cet emplacement [UTILISÉ]
    Route::get('locations/{id}/reservations', [LocationController::class, 'getActiveReservations']);

    // GET /api/locations-active → Emplacements actifs uniquement [UTILISÉ]
    Route::get('locations-active', [LocationController::class, 'activeLocations']);

    // GET /api/locations-warehouses → Liste des entrepôts distincts [UTILISÉ]
    Route::get('locations-warehouses', [LocationController::class, 'warehouses']);

    // ========================================================================
    // RÉCEPTIONS DE STOCK (préfixe: /api/stock-receipts)
    // Workflow : pending → shipped → in_transit → arrived → rated → validated
    // ========================================================================
    Route::prefix('stock-receipts')->group(function () {

        // --- Statistiques globales (avant les routes avec paramètres) ---
        // GET /api/stock-receipts/global-statistics → Stats globales (par statut, retards, valeurs) [UTILISÉ]
        Route::get('/global-statistics', [StockReceiptController::class, 'globalStatistics']);

        // --- CRUD de base ---
        // GET  /api/stock-receipts → Liste paginée avec filtres [UTILISÉ]
        Route::get('/', [StockReceiptController::class, 'index']);
        // POST /api/stock-receipts → Créer une commande fournisseur [UTILISÉ]
        Route::post('/', [StockReceiptController::class, 'store']);
        // GET  /api/stock-receipts/{id} → Détail complet (items, transactions, paiements) [UTILISÉ]
        Route::get('/{stockReceipt}', [StockReceiptController::class, 'show']);
        // PUT  /api/stock-receipts/{id} → Modifier (seulement si status=pending) [UTILISÉ]
        Route::put('/{stockReceipt}', [StockReceiptController::class, 'update']);

        // --- Progression du workflow de livraison ---
        // POST .../mark-shipped → Marquer comme "envoyé" [UTILISÉ]
        Route::post('/{stockReceipt}/mark-shipped', [StockReceiptController::class, 'markAsShipped']);
        // POST .../mark-in-transit → Marquer comme "en transit" [UTILISÉ]
        Route::post('/{stockReceipt}/mark-in-transit', [StockReceiptController::class, 'markAsInTransit']);
        // POST .../mark-arrived → Marquer comme "arrivé" + assigner emplacements [UTILISÉ]
        Route::post('/{stockReceipt}/mark-arrived', [StockReceiptController::class, 'markAsArrived']);
        // POST .../mark-rated → Marquer comme "évalué" [UTILISÉ]
        Route::post('/{stockReceipt}/mark-rated', [StockReceiptController::class, 'markAsRated']);

        // --- Validation et annulation ---
        // POST .../validate → Valider définitivement + mettre à jour scores fournisseur/transitaire [UTILISÉ]
        Route::post('/{stockReceipt}/validate', [StockReceiptController::class, 'validateReceipt']);
        // POST .../cancel → Annuler la réception [UTILISÉ]
        Route::post('/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel']);

        // --- Gestion des coûts ---
        // GET  .../cost-recommendations → Recommandations de répartition des coûts [UTILISÉ]
        Route::get('{stockReceipt}/cost-recommendations', [StockReceiptController::class, 'getCostRecommendations']);
        // POST .../apply-costs → Appliquer la répartition validée par l'utilisateur [UTILISÉ]
        Route::post('{stockReceipt}/apply-costs', [StockReceiptController::class, 'applyCosts']);

        // --- Dépenses annexes ---
        // POST .../expenses → Ajouter une dépense annexe [NON UTILISÉ]
        Route::post('{stockReceipt}/expenses', [StockReceiptController::class, 'addExpense']);
        // GET  .../expenses → Lister les dépenses annexes [NON UTILISÉ]
        Route::get('{stockReceipt}/expenses', [StockReceiptController::class, 'getExpenses']);

        // --- Paiements et évaluations ---
        // POST .../move-received-variant → Déplacer variante reçue vers un emplacement [UTILISÉ]
        Route::post('/{stockReceipt}/move-received-variant', [StockReceiptController::class, 'moveReceivedQuantity']);
        // POST .../payment → Enregistrer un paiement (fournisseur/transitaire) [UTILISÉ]
        Route::post('/{stockReceipt}/payment', [StockReceiptController::class, 'recordPayment']);
        // POST .../rate → Évaluer la qualité des items reçus [UTILISÉ]
        Route::post('/{stockReceipt}/rate', [StockReceiptController::class, 'rateReceipt']);
        // GET  .../cost-allocations → Répartition des coûts alloués [UTILISÉ]
        Route::get('/{stockReceipt}/cost-allocations', [StockReceiptController::class, 'getCostAllocated']);

        // --- Statistiques par réception ---
        // GET .../statistics → Stats (quantités, coûts, qualité, paiements) [UTILISÉ]
        Route::get('/{stockReceipt}/statistics', [StockReceiptController::class, 'statistics']);
    });

    // ========================================================================
    // MOUVEMENTS DE STOCK (préfixe: /api/stock-movements)
    // ========================================================================
    Route::prefix('stock-movements')->group(function () {
        // GET  /api/stock-movements → Liste de tous les mouvements (filtres, pagination) [UTILISÉ]
        Route::get('/', [StockMovementController::class, 'index']);
        // GET  .../statistics → Stats globales des mouvements [UTILISÉ]
        Route::get('/statistics', [StockMovementController::class, 'statistics']);
        // GET  .../grouped → Mouvements groupés par batch [UTILISÉ]
        Route::get('/grouped', [StockMovementController::class, 'grouped']);
        // GET  .../batch/{batchId} → Détails d'un batch de mouvements [UTILISÉ]
        Route::get('/batch/{batchId}', [StockMovementController::class, 'batchDetails']);
        // GET  ...//{id} → Détail d'un mouvement [UTILISÉ]
        Route::get('/{id}', [StockMovementController::class, 'show']);
        // POST .../transfer → Transfert simple (1 variante, loc A → loc B) [UTILISÉ]
        Route::post('/transfer', [StockMovementController::class, 'transfer']);
        // POST .../bulk-transfer → Transfert multiple (N variantes) [UTILISÉ]
        Route::post('/bulk-transfer', [StockMovementController::class, 'bulkTransfer']);
        // POST .../adjustment → Ajustement simple [UTILISÉ]
        Route::post('/adjustment', [StockMovementController::class, 'adjustment']);
        // POST .../bulk-adjustment → Ajustement multiple [UTILISÉ]
        Route::post('/bulk-adjustment', [StockMovementController::class, 'bulkAdjustment']);
        // POST .../loss → Déclarer une perte (casse, vol, expiration...) [UTILISÉ]
        Route::post('loss', [StockMovementController::class, 'declareLoss']);
        // POST .../reconcile-inventory → Réconciliation d'inventaire physique [UTILISÉ]
        Route::post('reconcile-inventory', [StockMovementController::class, 'reconcileInventory']);
    });

    // --- Historique par entité ---
    // GET /api/variants/{variantId}/movements → Historique mouvements d'une variante [NON UTILISÉ]
    Route::get('variants/{variantId}/movements', [StockMovementController::class, 'variantHistory']);
    // GET /api/locations/{locationId}/movements → Historique mouvements d'un emplacement [NON UTILISÉ]
    Route::get('locations/{locationId}/movements', [StockMovementController::class, 'locationHistory']);

    // ========================================================================
    // CLIENTS (préfixe: /api/customers)
    // ========================================================================
    Route::prefix('customers')->group(function () {

        // GET /api/customers/search-for-sale → Recherche rapide client pour la vente [UTILISÉ]
        Route::get('/search-for-sale', [CustomerController::class, 'searchForSale']);

        // GET  /api/customers → Liste paginée (filtres: search, status, reliability) [UTILISÉ]
        Route::get('/', [CustomerController::class, 'index']);
        // POST /api/customers → Créer un client [UTILISÉ]
        Route::post('/', [CustomerController::class, 'store']);
        // GET  /api/customers/{id} → Détail d'un client [UTILISÉ]
        Route::get('/{id}', [CustomerController::class, 'show']);
        // PUT  /api/customers/{id} → Modifier un client [UTILISÉ]
        Route::put('/{id}', [CustomerController::class, 'update']);

        // GET /api/customers/{id}/statistics → Stats client [NON UTILISÉ]
        Route::get('/{id}/statistics', [CustomerController::class, 'statistics']);

        // --- Gestion du score de fiabilité [NON UTILISÉ] ---
        Route::post('/{id}/adjust-score', [CustomerController::class, 'adjustScore']);
        Route::post('/{id}/recalculate-score', [CustomerController::class, 'recalculateScore']);

        // --- Points de fidélité [NON UTILISÉ] ---
        Route::post('/{id}/add-loyalty-points', [CustomerController::class, 'addLoyaltyPoints']);
        Route::post('/{id}/use-loyalty-points', [CustomerController::class, 'useLoyaltyPoints']);

        // --- Gestion du crédit client [NON UTILISÉ] ---
        Route::post('/{id}/adjust-credit-limit', [CustomerController::class, 'adjustCreditLimit']);
        Route::get('/{id}/can-get-credit', [CustomerController::class, 'canGetCredit']);

        // --- Historique par type de vente ---
        // GET /api/customers/{id}/sales-immediate → Ventes immédiates du client [UTILISÉ]
        Route::get('/{id}/sales-immediate', [CustomerController::class, 'getCustomerSaleImmediate']);
        // GET /api/customers/{id}/credits → Crédits du client [UTILISÉ]
        Route::get('/{id}/credits', [CustomerController::class, 'getCustomerCredits']);
        // GET /api/customers/{id}/reservations → Réservations du client [UTILISÉ]
        Route::get('/{id}/reservations', [CustomerController::class, 'getCustomerReservations']);
    });

    // ========================================================================
    // LOCALISATIONS DE VARIANTES (préfixe: /api/product-variant-locations)
    // Lien variante <-> emplacement (quantité en stock par emplacement)
    // ========================================================================
    Route::prefix('product-variant-locations')->group(function () {
        // GET    /api/product-variant-locations → Liste toutes les localisations [UTILISÉ]
        Route::get('/', [ProductVariantLocationController::class, 'index']);
        // POST   → Assigner une variante à un emplacement [UTILISÉ]
        Route::post('/', [ProductVariantLocationController::class, 'store']);
        // GET    /{id} → Détail [UTILISÉ]
        Route::get('/{id}', [ProductVariantLocationController::class, 'show']);
        // PUT    /{id} → Modifier [UTILISÉ]
        Route::put('/{id}', [ProductVariantLocationController::class, 'update']);
        // DELETE /{id} → Supprimer [UTILISÉ]
        Route::delete('/{id}', [ProductVariantLocationController::class, 'destroy']);
        // GET    /{id}/can-delete → Vérifier suppression possible [UTILISÉ]
        Route::get('/{id}/can-delete', [ProductVariantLocationController::class, 'canDelete']);
    });

    // GET /api/locations/{locationId}/variants → Variantes dans un emplacement (paginé) [UTILISÉ]
    Route::get('locations/{locationId}/variants', [ProductVariantLocationController::class, 'getByLocation']);
    // GET /api/locations/{locationId}/statistics → Stats emplacement (via PVL controller) [UTILISÉ]
    Route::get('locations/{locationId}/statistics', [ProductVariantLocationController::class, 'locationStatistics']);
    // GET /api/variants/{variantId}/locations → Emplacements d'une variante [UTILISÉ]
    Route::get('variants/{variantId}/locations', [ProductVariantLocationController::class, 'getByVariant']);

    // ========================================================================
    // VENTES (préfixe: /api/sales)
    // Types : immediate (cash), credit (échéances), reservation (acompte)
    // ========================================================================
    Route::prefix('sales')->group(function () {
        // GET /api/sales → Liste globale toutes ventes [NON UTILISÉ directement]
        Route::get('/', [SaleController::class, 'index']);
        // GET /api/sales/statistics → Stats globales des ventes [NON UTILISÉ]
        Route::get('/statistics', [SaleController::class, 'statistics']);
        // GET /api/sales/immediate → Liste ventes immédiates (paginé, filtres) [UTILISÉ]
        Route::get('/immediate', [SaleController::class, 'immediateSaleIndex']);
        // GET /api/sales/immediate/{id} → Détail vente immédiate [UTILISÉ]
        Route::get('/immediate/{id}', [SaleController::class, 'immediateSaleShow']);
        // GET /api/sales/{id} → Détail vente (tous types) [NON UTILISÉ directement]
        Route::get('/{id}', [SaleController::class, 'show']);

        // --- Création ---
        // POST /api/sales/immediate → Créer vente immédiate (cash) [UTILISÉ]
        Route::post('/immediate', [SaleController::class, 'storeImmediate']);
        // POST /api/sales/credit → Créer vente à crédit (avec échéancier) [UTILISÉ]
        Route::post('/credit', [SaleController::class, 'storeCredit']);
        // POST /api/sales/reservation → Créer réservation (avec acompte) [UTILISÉ]
        Route::post('/reservation', [SaleController::class, 'storeReservation']);

        // POST /api/sales/immediate/cancel/{sale} → Annuler vente immédiate [UTILISÉ]
        Route::post('/immediate/cancel/{sale}', [SaleController::class, 'cancelImmediateSale']);
    });

    // ========================================================================
    // CRÉDITS (préfixe: /api/credits)
    // ========================================================================
    Route::prefix('credits')->group(function () {
        // GET  /api/credits → Liste paginée des crédits [UTILISÉ]
        Route::get('/', [SaleController::class, 'indexCredits']);
        // GET  /api/credits/overdue → Crédits en retard [NON UTILISÉ]
        Route::get('/overdue', [SaleController::class, 'overdueCredits']);
        // GET  /api/credits/due-soon → Crédits dont l'échéance approche [NON UTILISÉ]
        Route::get('/due-soon', [SaleController::class, 'dueSoonCredits']);
        // GET  /api/credits/{id} → Détail crédit (avec échéances et paiements) [UTILISÉ]
        Route::get('/{id}', [SaleController::class, 'showCredit']);
        // POST /api/credits/{creditId}/installments/{installmentId}/pay → Payer une échéance [UTILISÉ]
        Route::post('/{creditId}/installments/{installmentId}/pay', [SaleController::class, 'payInstallment']);
        // POST /api/credits/cancel/{credit} → Annuler un crédit [UTILISÉ]
        Route::post('/cancel/{credit}', [SaleController::class, 'cancelCredit']);
    });

    // ========================================================================
    // RÉSERVATIONS (préfixe: /api/reservations)
    // ========================================================================
    Route::prefix('reservations')->group(function () {
        // GET  /api/reservations → Liste paginée [UTILISÉ]
        Route::get('/', [SaleController::class, 'indexReservations']);
        // GET  /api/reservations/expiring-soon → Expirent bientôt [NON UTILISÉ]
        Route::get('/expiring-soon', [SaleController::class, 'expiringSoonReservations']);
        // GET  /api/reservations/expired → Expirées [NON UTILISÉ]
        Route::get('/expired', [SaleController::class, 'expiredReservations']);
        // GET  /api/reservations/{id} → Détail [UTILISÉ]
        Route::get('/{id}', [SaleController::class, 'showReservation']);
        // POST /api/reservations/{id}/complete → Finaliser (paiement final) [UTILISÉ]
        Route::post('/{id}/complete', [SaleController::class, 'completeReservation']);
        // POST /api/reservations/{id}/cancel → Annuler [UTILISÉ]
        Route::post('/{id}/cancel', [SaleController::class, 'cancelReservation']);
        // POST /api/reservations/{id}/add-deposit → Ajouter un acompte supplémentaire [NON UTILISÉ]
        Route::post('/{id}/add-deposit', [SaleController::class, 'addDeposit']);
    });

    // ========================================================================
    // DÉPENSES PLANIFIÉES (préfixe: /api/planned-expenses)
    // ========================================================================
    Route::prefix('planned-expenses')->group(function () {
        // GET    /api/planned-expenses → Liste paginée [UTILISÉ]
        Route::get('/', [PlannedExpenseController::class, 'index']);
        // POST   → Créer une dépense planifiée [UTILISÉ]
        Route::post('/', [PlannedExpenseController::class, 'store']);
        // GET    /api/planned-expenses/stats → Statistiques [UTILISÉ]
        Route::get('/stats', [PlannedExpenseController::class, 'stats']);
        // GET    /{id} → Détail [UTILISÉ]
        Route::get('/{plannedExpense}', [PlannedExpenseController::class, 'show']);
        // PUT    /{id} → Modifier [UTILISÉ]
        Route::put('/{plannedExpense}', [PlannedExpenseController::class, 'update']);
        // POST   /{id}/mark-paid → Marquer comme payée [UTILISÉ]
        Route::post('/{plannedExpense}/mark-paid', [PlannedExpenseController::class, 'markPaid']);
        // DELETE /{id} → Supprimer [UTILISÉ]
        Route::delete('/{plannedExpense}', [PlannedExpenseController::class, 'destroy']);
        // GET    /{id}/transactions → Transactions liées à cette dépense [UTILISÉ]
        Route::get('/{plannedExpense}/transactions', [PlannedExpenseController::class, 'transactions']);
    });

    // ========================================================================
    // STATISTIQUES DE VENTE (préfixe: /api/statistics/sales)
    // ========================================================================
    Route::prefix('statistics/sales')->group(function () {
        // GET .../overview → Vue d'ensemble (CA, marge, nb ventes...) [UTILISÉ]
        Route::get('overview', [SalesStatisticsController::class, 'overview']);
        // GET .../timeline → Évolution temporelle des ventes [UTILISÉ]
        Route::get('timeline', [SalesStatisticsController::class, 'timeline']);
        // GET .../top-products → Produits les plus vendus [UTILISÉ]
        Route::get('top-products', [SalesStatisticsController::class, 'topProducts']);
        // GET .../by-category → Ventes par catégorie [UTILISÉ]
        Route::get('by-category', [SalesStatisticsController::class, 'byCategory']);
        // GET .../by-seller → Ventes par vendeur [UTILISÉ]
        Route::get('by-seller', [SalesStatisticsController::class, 'bySeller']);
        // GET .../by-payment-method → Ventes par méthode de paiement [UTILISÉ]
        Route::get('by-payment-method', [SalesStatisticsController::class, 'byPaymentMethod']);
        // GET .../discounts → Analyse des remises [UTILISÉ]
        Route::get('discounts', [SalesStatisticsController::class, 'discounts']);
        // GET .../credits → Analyse des crédits [UTILISÉ]
        Route::get('credits', [SalesStatisticsController::class, 'credits']);
        // GET .../reservations → Analyse des réservations [UTILISÉ]
        Route::get('reservations', [SalesStatisticsController::class, 'reservations']);
    });

    // ========================================================================
    // STATISTIQUES FINANCIÈRES (préfixe: /api/statistics/financial)
    // ========================================================================
    Route::prefix('statistics/financial')->group(function () {
        // GET .../dashboard → Dashboard financier global [UTILISÉ]
        Route::get('dashboard', [SalesStatisticsController::class, 'financialDashboard']);
        // GET .../timeline → Évolution financière dans le temps [UTILISÉ]
        Route::get('timeline', [SalesStatisticsController::class, 'financialTimeline']);
        // GET .../profits → Détails des profits [UTILISÉ]
        Route::get('profits', [SalesStatisticsController::class, 'profitsOverview']);
        // GET .../expenses → Détails des dépenses [UTILISÉ]
        Route::get('expenses', [SalesStatisticsController::class, 'expensesOverview']);
        // GET .../losses → Détails des pertes [UTILISÉ]
        Route::get('losses', [SalesStatisticsController::class, 'lossesOverview']);
    });

    // ========================================================================
    // DASHBOARD
    // ========================================================================
    // GET /api/dashboard → Données agrégées du tableau de bord [UTILISÉ]
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ========================================================================
    // FACTURES PDF (préfixe: /api/invoices)
    // Génération et téléchargement de documents PDF (DomPDF)
    // ========================================================================
    Route::prefix('invoices')->middleware('auth:sanctum')->group(function () {
        // GET .../download-sale → Facture vente immédiate [UTILISÉ]
        Route::get('/{sale}/download-sale', [InvoiceController::class, 'downloadSale']);
        // GET .../download-credit → Facture crédit [UTILISÉ]
        Route::get('/{credit}/download-credit', [InvoiceController::class, 'downloadCredit']);
        // GET .../download-transaction-installment → Reçu paiement échéance [UTILISÉ]
        Route::get('/{installmentTransaction}/download-transaction-installment', [InvoiceController::class, 'downloadPaymentReceipt']);
        // GET .../download-reservation → Document réservation [UTILISÉ]
        Route::get('/{reservation}/download-reservation', [InvoiceController::class, 'downloadReservation']);
        // GET .../download-reservation-receipt → Reçu finalisation réservation [UTILISÉ]
        Route::get('/{reservation}/download-reservation-receipt', [InvoiceController::class, 'downloadReservationReceipt']);
        // GET .../download-cash-count → Rapport comptage de caisse [UTILISÉ]
        Route::get('/{cashCount}/download-cash-count', [InvoiceController::class, 'downloadCashCount']);
    });

    // ========================================================================
    // COMPTAGE DE CAISSE (préfixe: /api/cash-counts)
    // ========================================================================
    // GET  /api/cash-counts      → Liste des comptages [UTILISÉ]
    // POST /api/cash-counts      → Créer un comptage [UTILISÉ]
    // GET  /api/cash-counts/{id} → Détail [UTILISÉ]
    // PUT  /api/cash-counts/{id} → Modifier [UTILISÉ]
    Route::apiResource('cash-counts', CashCountController::class)->only(['index', 'store', 'show', 'update']);

    // ========================================================================
    // INFOS ENTREPRISE
    // ========================================================================
    // GET /api/company-info → Nom, téléphone, adresse, email, logo [UTILISÉ]
    Route::get('company-info', [CompanyInfoController::class, 'index']);
    // PUT /api/company-info → Modifier les infos [UTILISÉ]
    Route::put('company-info', [CompanyInfoController::class, 'update']);

    // ========================================================================
    // SYSTÈME
    // ========================================================================
    // GET /api/system/info → Infos serveur (IP, hostname, versions...) [NON UTILISÉ]
    Route::get('/system/info', [SystemController::class, 'getServerInfo']);

    // ========================================================================
    // IMPRESSION THERMIQUE POS (préfixe: /api/print)
    // Impression directe sur imprimante USB (ESC/POS via mike42/escpos-php)
    // ========================================================================
    Route::prefix('print')->group(function () {
        // GET /api/print/test → Test imprimante [UTILISÉ]
        Route::get('/test', [PrintController::class, 'testPrinter']);
        // GET /api/print/sale/{id} → Imprimer ticket vente [UTILISÉ]
        Route::get('/sale/{id}', [PrintController::class, 'printSale']);
        // GET /api/print/credit/{credit} → Imprimer ticket crédit [UTILISÉ]
        Route::get('/credit/{credit}', [PrintController::class, 'printCredit']);
        // GET /api/print/reservation/{id} → Imprimer ticket réservation [UTILISÉ]
        Route::get('/reservation/{reservation}', [PrintController::class, 'printReservation']);
        // GET /api/print/reservation-receipt/{id} → Imprimer reçu finalisation [UTILISÉ]
        Route::get('/reservation-receipt/{reservation}', [PrintController::class, 'printReservationReceipt']);
        // GET /api/print/cash-count/{id} → Imprimer rapport comptage [UTILISÉ]
        Route::get('/cash-count/{cashCount}', [PrintController::class, 'printCashCount']);
        // GET /api/print/installment-transaction/{id} → Imprimer reçu paiement échéance [UTILISÉ]
        Route::get('/installment-transaction/{installmentTransaction}', [PrintController::class, 'printInstallmentTransaction']);
    });

    // ========================================================================
    // JOURNAUX D'ACTIVITÉ (préfixe: /api/activity-logs)
    // ========================================================================
    Route::prefix('activity-logs')->group(function () {
        // --- Consultation ---
        // GET /api/activity-logs → Liste paginée (filtres: user, action, model, dates) [UTILISÉ]
        Route::get('/', [ActivityLogController::class, 'index']);
        // GET .../statistics → Stats globales [UTILISÉ]
        Route::get('/statistics', [ActivityLogController::class, 'statistics']);
        // GET .../failures → Logs d'échec uniquement [UTILISÉ]
        Route::get('/failures', [ActivityLogController::class, 'failures']);
        // GET .../actions-by-category → Actions groupées par catégorie [UTILISÉ]
        Route::get('/actions-by-category', [ActivityLogController::class, 'actionsByCategory']);
        // GET .../by-model → Logs d'un modèle spécifique (type + id) [UTILISÉ]
        Route::get('/by-model', [ActivityLogController::class, 'byModel']);
        // GET ...//{id} → Détail d'un log [UTILISÉ]
        Route::get('/{activityLog}', [ActivityLogController::class, 'show']);

        // --- Suppression (admin uniquement) ---
        Route::middleware(['role:admin'])->group(function () {
            // POST .../delete-between-dates → Supprimer entre deux dates [UTILISÉ]
            Route::post('/delete-between-dates', [ActivityLogController::class, 'deleteBetweenDates']);
            // POST .../delete-older-than → Supprimer plus anciens que X jours [UTILISÉ]
            Route::post('/delete-older-than', [ActivityLogController::class, 'deleteOlderThan']);
            // POST .../delete-by-status → Supprimer par statut [UTILISÉ]
            Route::post('/delete-by-status', [ActivityLogController::class, 'deleteByStatus']);
            // POST .../auto-cleanup → Nettoyage automatique [UTILISÉ]
            Route::post('/auto-cleanup', [ActivityLogController::class, 'autoCleanup']);
        });
    });
    // ========================================================================
    // CONFIGURATION api key LLM (préfixe:/api/ai-providers)
    // ========================================================================


    // GET => index
    //  POST => store
    //  PUT => update
    //  DELETE => destroy
    Route::prefix('ai-providers')->group(function () {
        Route::get('/', [AiProviderConfigController::class, 'index']);
        Route::post('/', [AiProviderConfigController::class, 'store']);
        Route::get('{id}', [AiProviderConfigController::class, 'show']);
        Route::put('{id}', [AiProviderConfigController::class, 'update']);
        Route::delete('{id}', [AiProviderConfigController::class, 'destroy']);
        Route::post('{id}/test',[AiProviderConfigController::class,'testConnection']);
        Route::post('{id}/refresh-models',[AiProviderConfigController::class,'refreshModels']);
        Route::post('{id}/set-default',[AiProviderConfigController::class,'setDefault']);
        Route::post('{id}/reset-usage',[AiProviderConfigController::class,'resetUsage']);
    });

    // ========================================================================
    // AI TASKS (préfixe: /api/ai/tasks)
    // ========================================================================
    Route::prefix('ai')->group(function () {
        // POST /api/ai/tasks → Créer une demande AI (exécution en queue)
        Route::post('tasks', [AiTaskController::class, 'store']);
        // POST /api/ai/chat-sync → Chat synchrone (sans queue)
        Route::post('chat-sync', [AiTaskController::class, 'chatSync']);
        // GET /api/ai/rag/status → Statut RAG (service Python)
        Route::get('rag/status', [AiTaskController::class, 'ragStatus']);
        // GET /api/ai/rag/manifest → Manifest pages (service Python)
        Route::get('rag/manifest', [AiTaskController::class, 'ragManifest']);
        // POST /api/ai/rag/refresh → Refresh RAG (service Python)
        Route::post('rag/refresh', [AiTaskController::class, 'ragRefresh']);
        // POST /api/ai/langgraph/query → Chat LangGraph (FAQ + PDF RAG)
        Route::post('langgraph/query', [AiTaskController::class, 'langgraphQuery']);
        // POST /api/ai/langgraph/rag/faqs → Ingestion FAQ FR/MG vers LangGraph
        Route::post('langgraph/rag/faqs', [AiTaskController::class, 'langgraphIngestFaqs']);
        // POST /api/ai/langgraph/rag/faqs/pairs → Ingestion FAQ paires FR/MG
        Route::post('langgraph/rag/faqs/pairs', [AiTaskController::class, 'langgraphIngestFaqPairs']);
        // POST /api/ai/langgraph/rag/pdfs → Ingestion PDF vers LangGraph
        Route::post('langgraph/rag/pdfs', [AiTaskController::class, 'langgraphIngestPdfs']);
        // POST /api/ai/langgraph/rag/page-routes → Ingestion page_routes.json vers LangGraph
        Route::post('langgraph/rag/page-routes', [AiTaskController::class, 'langgraphIngestPageRoutes']);
        // GET /api/ai/langgraph/rag/status → Statut RAG LangGraph
        Route::get('langgraph/rag/status', [AiTaskController::class, 'langgraphRagStatus']);
        // GET /api/ai/langgraph/conversations → Historique conversations LangGraph
        Route::get('langgraph/conversations', [AiTaskController::class, 'langgraphConversations']);
        // GET /api/ai/langgraph/messages → Messages user/assistant d'une session
        Route::get('langgraph/messages', [AiTaskController::class, 'langgraphMessages']);
        // GET /api/ai/tasks → Liste des tâches AI (filtres + pagination)
        Route::get('tasks', [AiTaskController::class, 'index']);
        // GET /api/ai/tasks/{id} → Détail d'une tâche AI
        Route::get('tasks/{id}', [AiTaskController::class, 'show']);
        // PATCH /api/ai/tasks/{id}/executed → Marquer exécutée côté frontend
        Route::patch('tasks/{id}/executed', [AiTaskController::class, 'markExecuted']);
    });
   

    // teste de connexion vers un api


});

// ============================================================================
// NOTIFICATIONS (préfixe: /api/notifications)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('notifications')->group(function () {
        // GET    /api/notifications → Liste paginée [UTILISÉ]
        Route::get('/', [NotificationController::class, 'index']);
        // GET    .../count → Compteur (non lues, total) [UTILISÉ]
        Route::get('/count', [NotificationController::class, 'count']);
        // PATCH  .../{id}/read → Marquer comme lue [UTILISÉ]
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
        // DELETE .../{id}/dismiss → Supprimer (dismiss) [UTILISÉ]
        Route::delete('/{notification}/dismiss', [NotificationController::class, 'dismiss']);
        // POST   .../mark-all-read → Marquer toutes comme lues [UTILISÉ]
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        // POST   .../dismiss-by-type → Supprimer par type [UTILISÉ]
        Route::post('/dismiss-by-type', [NotificationController::class, 'dismissByType']);
        // GET    .../preferences → Préférences de notification [UTILISÉ]
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        // PUT    .../preferences/{id} → Modifier une préférence [UTILISÉ]
        Route::put('/preferences/{preference}', [NotificationController::class, 'updatePreference']);
        // POST   .../generate → Forcer la génération (admin) [UTILISÉ]
        Route::post('/generate', [NotificationController::class, 'generate'])->middleware('admin');
    });
});
