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
use  App\Http\Controllers\StockPaymentController;
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
use Illuminate\Routing\RouteUri;

Route::get('/ping', fn () => response()->json(['status' => 'ok']));
// toutes les routes d'auth sous le préfixe "auth""
Route::prefix('auth')->group(function () {
    // routes publiques (login/register)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // routes protégées (nécessitent authentification)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

});

// Routes publiques (ou protégées par auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {

         // Gestion utilisateurs (admin)
    Route::get('/users/all', [ UserController::class, 'indexUsers']);

    Route::patch('/users/{id}/toggle', [UserController::class, 'toggleStatus']);
    Route::apiResource("users",UserController::class)->only(['index','store','update']);
    Route::get('categories/with-products', [CategoryController::class, 'withProducts']);
    Route::get('categories/for-sale', [CategoryController::class, 'forSale']);
    // Catégories
    Route::apiResource('categories', CategoryController::class);

    Route::get('products/for-sale', [ProductController::class, 'getProductsForSale']);
    // Produits
    Route::apiResource('products', ProductController::class);

    Route::get('products/with-variants/{id}', [ProductController::class, 'showWithVariants']);
    Route::get('products/{id}/statistics', [ProductController::class, 'statistics']);
    
    // Variantes
    Route::get('products/{productId}/variants', [ProductVariantController::class, 'index']);
    Route::post('products/{productId}/variants', [ProductVariantController::class, 'store']);
    Route::put('products/{productId}/variants/{id}', [ProductVariantController::class, 'update']);
    Route::delete('products/{productId}/variants/{id}', [ProductVariantController::class, 'destroy']);
    Route::get('products/{productId}/variants/{id}', [ProductVariantController::class, 'show']);
    Route::post('products/update-base-prices', [ProductController::class, 'updateBasePrices']);
    // routes/api.php
    Route::get('products/{product}/attribute-types', [ProductController::class, 'getProductAttributeTypes']);
    // Dans routes/api.php
    Route::get('products/{productId}/variants/{variantId}/batches', [ProductController::class, 'getVariantBatches']);


    // Types d'attributs
    Route::apiResource('freight-forwarders', FreightForwarderController::class);
    Route::get('freight-forwarders/{id}/statistics', [FreightForwarderController::class, 'statistics']);
    Route::get('/freight-forwarders/{id}/stock-receipts', [FreightForwarderController::class, 'getStockReceipts']);
    Route::apiResource('attribute-types', AttributeTypeController::class);

    Route::get('suppliers/{id}/statistics', [SupplierController::class, 'statistics']);
    Route::apiResource('suppliers', SupplierController::class);
   
    Route::get('suppliers/{id}/stock-receipts', [SupplierController::class, 'getStockReceipts']);


    Route::get('coordinates/countries', [CoordinateController::class, 'countries']);
    Route::get('coordinates/cities/{country}', [CoordinateController::class, 'citiesByCountry']);
    Route::get('coordinates/{id}/usage', [CoordinateController::class, 'usage']);


    Route::apiResource('coordinates', CoordinateController::class);


    Route::post('/upload-image', [FileUploadController::class, 'uploadImage']);
    Route::delete('/delete-image', [FileUploadController::class, 'deleteImage']);


    

    // Routes accessibles à tous les utilisateurs authentifiés
    Route::prefix('currency-rates')->group(function () {
        Route::get('current', [CurrencyRateController::class, 'current']);
        Route::get('at-date', [CurrencyRateController::class, 'atDate']);
        Route::post('convert', [CurrencyRateController::class, 'convert']);
        Route::get('check-freshness', [CurrencyRateController::class, 'checkFreshness']);
    });
    Route::apiResource('currency-rates',CurrencyRateController::class);


    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('currency-rates', CurrencyRateController::class);
    });

    Route::apiResource('account-types', AccountTypeController::class);
    
    // ==================== TYPES DE TRANSACTIONS ====================
    Route::apiResource('transaction-types', TransactionTypeController::class);
    
    // ==================== CATÉGORIES DE DÉPENSES ====================
    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::patch('expense-categories/{expenseCategory}/toggle-status', 
        [ExpenseCategoryController::class, 'toggleStatus']);
    

    // ==================== COMPTES MONÉTAIRES ====================
    Route::get('accounts/cash-mobile-money',[AccountController::class,'getCashMobileMoney']);
    Route::apiResource('accounts', AccountController::class)->except(['destroy']);
    Route::get(
        '/accounts/{account}/transactions',
        [AccountTransactionController::class, 'index']
    );
    // Actions spéciales sur les comptes
    Route::patch('accounts/{account}/activate', [AccountController::class, 'activate']);
    Route::patch('accounts/{account}/deactivate', [AccountController::class, 'deactivate']);
    Route::get('accounts/{account}/stats', [AccountController::class, 'stats']);
    Route::get('accounts/{account}/balance-at-date', [AccountController::class, 'balanceAtDate']);
    
    // ==================== TRANSACTIONS ====================
    Route::post('transactions/expense-operational', 
    [AccountTransactionController::class, 'storeOperationalExpense']);
    Route::get('transactions/expense-operational',[AccountTransactionController::class, 'indexExpenseOperation']);
    Route::apiResource('transactions', AccountTransactionController::class)
        ->only([ 'show', 'store']);
    
    // Actions spéciales sur les transactions
    Route::post('transactions/transfer', [AccountTransactionController::class, 'transfer']);

    Route::post('transactions/{transaction}/cancel', [AccountTransactionController::class, 'cancel']);
    
    // ==================== VUE GLOBALE TRÉSORERIE ====================
    Route::prefix('treasury')->group(function() {
        Route::get('overview', [TreasuryController::class, 'overview']);
        Route::get('with-currencies', [TreasuryController::class, 'withCurrencies']);
        Route::get('stats', [TreasuryController::class, 'stats']);
    });
    
    // // ==================== PAIEMENTS RÉAPPROVISIONNEMENT ====================
    Route::prefix('stock-payments')->group(function() {
        Route::post('supplier', [StockPaymentController::class, 'paySupplier']);
        Route::post('freight', [StockPaymentController::class, 'payFreight']);
        Route::post('complete', [StockPaymentController::class, 'payComplete']);
        Route::get('receipt/{stock_receipt_id}', [StockPaymentController::class, 'getReceiptTransactions']);
    });

    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{id}/can-delete', [LocationController::class, 'canDelete']);
    Route::get('locations/{id}/variants-detail', [LocationController::class, 'getVariantsDetail']);
    Route::get('locations/{id}/stock-movements', [LocationController::class, 'getStockMovements']);
    Route::get('locations/{id}/statistics', [LocationController::class, 'statistics']);
    Route::get('locations/{id}/reservations', [LocationController::class, 'getActiveReservations']);
    Route::get('locations-active', [LocationController::class, 'activeLocations']);
    Route::get('locations-warehouses', [LocationController::class, 'warehouses']);

    Route::prefix('stock-receipts')->group(function() {
        // Statistiques globales (doit être avant les routes avec paramètres)
        Route::get('/global-statistics', [StockReceiptController::class, 'globalStatistics']);
        
        // CRUD de base
        Route::get('/', [StockReceiptController::class, 'index']);
        Route::post('/', [StockReceiptController::class, 'store']);
        Route::get('/{stockReceipt}', [StockReceiptController::class, 'show']);
        Route::put('/{stockReceipt}', [StockReceiptController::class, 'update']);
        
        // Gestion du statut de livraison
        Route::post('/{stockReceipt}/mark-shipped', [StockReceiptController::class, 'markAsShipped']);
        Route::post('/{stockReceipt}/mark-in-transit', [StockReceiptController::class, 'markAsInTransit']);
        Route::post('/{stockReceipt}/mark-arrived', [StockReceiptController::class, 'markAsArrived']);
        Route::post('/{stockReceipt}/mark-rated', [StockReceiptController::class, 'markAsRated']);
        
        // Validation finale et mise à jour des scores
        Route::post('/{stockReceipt}/validate', [StockReceiptController::class, 'validateReceipt']);
        Route::post('/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel']);
        
        // Recommandations de COÛTS (pour le frontend)
        Route::get('{stockReceipt}/cost-recommendations', [StockReceiptController::class, 'getCostRecommendations']);

        // Appliquer les coûts validés par l'utilisateur
        Route::post('{stockReceipt}/apply-costs', [StockReceiptController::class, 'applyCosts']);


        // Ajouter une dépense
        Route::post('{stockReceipt}/expenses', [StockReceiptController::class, 'addExpense']);

        // Lister les dépenses
        Route::get('{stockReceipt}/expenses', [StockReceiptController::class, 'getExpenses']);
        // Paiements et évaluations
        Route::post('/{stockReceipt}/move-received-variant', [StockReceiptController::class, 'moveReceivedQuantity']);
        Route::post('/{stockReceipt}/payment', [StockReceiptController::class, 'recordPayment']);
        Route::post('/{stockReceipt}/rate', [StockReceiptController::class, 'rateReceipt']);
        Route::get('/{stockReceipt}/cost-allocations', [StockReceiptController::class, 'getCostAllocated']);
        
        // Statistiques
        Route::get('/{stockReceipt}/statistics', [StockReceiptController::class, 'statistics']);
    });
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [StockMovementController::class, 'index']);
        Route::get('/statistics', [StockMovementController::class, 'statistics']);
        Route::get('/grouped', [StockMovementController::class, 'grouped']);
        Route::get('/batch/{batchId}', [StockMovementController::class, 'batchDetails']);
        Route::get('/{id}', [StockMovementController::class, 'show']);
        Route::post('/transfer', [StockMovementController::class, 'transfer']);
        Route::post('/bulk-transfer', [StockMovementController::class, 'bulkTransfer']);
        Route::post('/adjustment', [StockMovementController::class, 'adjustment']);
        Route::post('/bulk-adjustment', [StockMovementController::class, 'bulkAdjustment']);
            // Déclaration de perte
        Route::post('loss', [StockMovementController::class, 'declareLoss']);
        
        // Réconciliation d'inventaire
        Route::post('reconcile-inventory', [StockMovementController::class, 'reconcileInventory']);
    });

    // Routes pour l'historique par variante ou location
    Route::get('variants/{variantId}/movements', [StockMovementController::class, 'variantHistory']);
    Route::get('locations/{locationId}/movements', [StockMovementController::class, 'locationHistory']);

    // ==================== CLIENTS ====================
    Route::prefix('customers')->group(function () {
        Route::get('/search-for-sale',[CustomerController::class, 'searchForSale']);
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);

        
        // Statistiques
        Route::get('/{id}/statistics', [CustomerController::class, 'statistics']);
        
        // Gestion du score de fiabilité
        Route::post('/{id}/adjust-score', [CustomerController::class, 'adjustScore']);
        Route::post('/{id}/recalculate-score', [CustomerController::class, 'recalculateScore']);
        
        // Gestion des points de fidélité
        Route::post('/{id}/add-loyalty-points', [CustomerController::class, 'addLoyaltyPoints']);
        Route::post('/{id}/use-loyalty-points', [CustomerController::class, 'useLoyaltyPoints']);
        
        // Gestion du crédit
        Route::post('/{id}/adjust-credit-limit', [CustomerController::class, 'adjustCreditLimit']);
        Route::get('/{id}/can-get-credit', [CustomerController::class, 'canGetCredit']);
        Route::get('/{id}/sales-immediate', [CustomerController::class, 'getCustomerSaleImmediate']);
        Route::get('/{id}/credits', [CustomerController::class, 'getCustomerCredits']);
        Route::get('/{id}/reservations', [CustomerController::class, 'getCustomerReservations']);
        
    });

    // ==================== LOCALISATIONS DE VARIANTES ====================
    Route::prefix('product-variant-locations')->group(function () {
        Route::get('/', [ProductVariantLocationController::class, 'index']);
        Route::post('/', [ProductVariantLocationController::class, 'store']);
        Route::get('/{id}', [ProductVariantLocationController::class, 'show']);
        Route::put('/{id}', [ProductVariantLocationController::class, 'update']);
        Route::delete('/{id}', [ProductVariantLocationController::class, 'destroy']);
        Route::get('/{id}/can-delete', [ProductVariantLocationController::class, 'canDelete']);
    });

    // Routes pour obtenir les variantes par location
    Route::get('locations/{locationId}/variants', [ProductVariantLocationController::class, 'getByLocation']);
    Route::get('locations/{locationId}/statistics', [ProductVariantLocationController::class, 'locationStatistics']);
    
    // Routes pour obtenir les locations par variante
    Route::get('variants/{variantId}/locations', [ProductVariantLocationController::class, 'getByVariant']);

    // ==================== VENTES ====================
    Route::prefix('sales')->group(function () {
        // Liste et détails des ventes
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/statistics', [SaleController::class, 'statistics']);
        Route::get('/immediate',[SaleController::class, 'immediateSaleIndex']);
        Route::get('/immediate/{id}',[SaleController::class,'immediateSaleShow']);
        Route::get('/{id}', [SaleController::class, 'show']);
       
        
        // Création de ventes par type
        Route::post('/immediate', [SaleController::class, 'storeImmediate']);
        Route::post('/credit', [SaleController::class, 'storeCredit']);
        Route::post('/reservation', [SaleController::class, 'storeReservation']);
        Route::post('/immediate/cancel/{sale}',[SaleController::class, 'cancelImmediateSale']);
    });

    // ==================== CRÉDITS ====================
    Route::prefix('credits')->group(function () {
        Route::get('/', [SaleController::class, 'indexCredits']);
        Route::get('/overdue', [SaleController::class, 'overdueCredits']);
        Route::get('/due-soon', [SaleController::class, 'dueSoonCredits']);
        Route::get('/{id}', [SaleController::class, 'showCredit']);
        Route::post('/{creditId}/installments/{installmentId}/pay', [SaleController::class, 'payInstallment']);
        Route::post('/cancel/{credit}', [SaleController::class, 'cancelCredit']);
    });

    // ==================== RÉSERVATIONS ====================
    Route::prefix('reservations')->group(function () {
        Route::get('/', [SaleController::class, 'indexReservations']);
        Route::get('/expiring-soon', [SaleController::class, 'expiringSoonReservations']);
        Route::get('/expired', [SaleController::class, 'expiredReservations']);
        Route::get('/{id}', [SaleController::class, 'showReservation']);
        Route::post('/{id}/complete', [SaleController::class, 'completeReservation']);
        Route::post('/{id}/cancel', [SaleController::class, 'cancelReservation']);
        Route::post('/{id}/add-deposit', [SaleController::class, 'addDeposit']);
    });



    Route::prefix('statistics/sales')->group(function () {
        Route::get('overview', [SalesStatisticsController::class, 'overview']);
        Route::get('timeline', [SalesStatisticsController::class, 'timeline']);
        Route::get('top-products', [SalesStatisticsController::class, 'topProducts']);
        Route::get('by-category', [SalesStatisticsController::class, 'byCategory']);
        Route::get('by-seller', [SalesStatisticsController::class, 'bySeller']);
        Route::get('by-payment-method', [SalesStatisticsController::class, 'byPaymentMethod']);
        Route::get('discounts', [SalesStatisticsController::class, 'discounts']);
        Route::get('credits', [SalesStatisticsController::class, 'credits']);
        Route::get('reservations', [SalesStatisticsController::class, 'reservations']);
    });
    Route::get('dashboard', [DashboardController::class, 'index']);
    

    Route::prefix('invoices')->middleware('auth:sanctum')->group(function () {
        Route::get('/{sale}/download-sale', [InvoiceController::class, 'downloadSale']);
        Route::get('/{credit}/download-credit', [InvoiceController::class, 'downloadCredit']);
        Route::get('/{installmentTransaction}/download-transaction-installment', [InvoiceController::class, 'downloadPaymentReceipt']);
        Route::get('/{reservation}/download-reservation', [InvoiceController::class, 'downloadReservation']);
        Route::get('/{reservation}/download-reservation-receipt', [InvoiceController::class, 'downloadReservationReceipt']);
        Route::get('/{cashCount}/download-cash-count', [InvoiceController::class, 'downloadCashCount']);
    });

    Route::apiResource('cash-counts', CashCountController::class)->only(['index','store','show','update']);   
    Route::get('company-info', [CompanyInfoController::class,'index']);
    Route::put('company-info', [CompanyInfoController::class,'update']);

    Route::get('/system/info', [SystemController::class, 'getServerInfo']);
    
    // routes/api.php
    Route::prefix('print')->group(function () {
        Route::get('/test', [PrintController::class, 'testPrinter']);
        Route::get('/sale/{id}', [PrintController::class, 'printSale']);
        Route::get('/credit/{credit}',[PrintController::class, 'printCredit']);
        Route::get('/reservation/{reservation}',[PrintController::class, 'printReservation']);
        Route::get('/reservation-receipt/{reservation}',[PrintController::class, 'printReservationReceipt']);
        Route::get('/cash-count/{cashCount}', [PrintController::class, 'printCashCount']);
        Route::get('/installment-transaction/{installmentTransaction}', [PrintController::class, 'printInstallmentTransaction']);
        
    });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/count', [NotificationController::class, 'count']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{notification}/dismiss', [NotificationController::class, 'dismiss']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/dismiss-by-type', [NotificationController::class, 'dismissByType']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::put('/preferences/{preference}', [NotificationController::class, 'updatePreference']);
        Route::post('/generate', [NotificationController::class, 'generate'])->middleware('admin');
    });
});

