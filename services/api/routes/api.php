<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttributeTypeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashCountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\CoordinateController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CurrencyRateController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FreightForwarderController;
use App\Http\Controllers\ImmediateSaleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlannedExpenseController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProductVariantLocationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesStatisticsController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockPaymentController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|==========================================================================
| API Routes — Express Sale
|==========================================================================
*/

Route::get('/ping', fn () => response()->json(['status' => 'ok']));

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    // UTILISATEURS
    Route::get('/users/all', [UserController::class, 'indexUsers']);
    Route::patch('/users/{id}/toggle', [UserController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'update']);

    // CATÉGORIES
    Route::get('categories/with-products', [CategoryController::class, 'withProducts']);
    Route::get('categories/for-sale', [CategoryController::class, 'forSale']);
    Route::apiResource('categories', CategoryController::class);

    // PRODUITS
    Route::get('products/for-sale', [ProductController::class, 'getProductsForSale']);
    Route::get('/products/attributes/available', [ProductController::class, 'getAvailableAttributes']);
    Route::apiResource('products', ProductController::class);
    Route::get('products/with-variants/{id}', [ProductController::class, 'showWithVariants']);
    Route::get('products/{id}/statistics', [ProductController::class, 'statistics']);
    Route::post('products/update-base-prices', [ProductController::class, 'updateBasePrices']);
    Route::get('products/{product}/attribute-types', [ProductController::class, 'getProductAttributeTypes']);
    Route::get('products/{productId}/variants/{variantId}/batches', [ProductController::class, 'getVariantBatches']);

    // VARIANTES PRODUIT
    Route::get('products/{productId}/variants', [ProductVariantController::class, 'index']);
    Route::post('products/{productId}/variants', [ProductVariantController::class, 'store']);
    Route::put('products/{productId}/variants/{id}', [ProductVariantController::class, 'update']);
    Route::delete('products/{productId}/variants/{id}', [ProductVariantController::class, 'destroy']);
    Route::get('products/{productId}/variants/{id}', [ProductVariantController::class, 'show']);

    // VALEURS D'ATTRIBUTS
    Route::prefix('attribute-types/{attributeType}')->group(function () {
        Route::get('values', [AttributeValueController::class, 'index']);
        Route::post('values', [AttributeValueController::class, 'store']);
        Route::get('values/{attributeValue}', [AttributeValueController::class, 'show']);
        Route::put('values/{attributeValue}', [AttributeValueController::class, 'update']);
        Route::delete('values/{attributeValue}', [AttributeValueController::class, 'destroy']);
        Route::post('values/reorder', [AttributeValueController::class, 'reorder']);
    });

    // TYPES D'ATTRIBUTS
    Route::apiResource('attribute-types', AttributeTypeController::class);

    // TRANSITAIRES
    Route::apiResource('freight-forwarders', FreightForwarderController::class);
    Route::get('freight-forwarders/{id}/statistics', [FreightForwarderController::class, 'statistics']);
    Route::get('/freight-forwarders/{id}/stock-receipts', [FreightForwarderController::class, 'getStockReceipts']);

    // FOURNISSEURS
    Route::get('suppliers/{id}/statistics', [SupplierController::class, 'statistics']);
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('suppliers/{id}/stock-receipts', [SupplierController::class, 'getStockReceipts']);

    // COORDONNÉES GÉOGRAPHIQUES
    Route::get('coordinates/countries', [CoordinateController::class, 'countries']);
    Route::get('coordinates/cities/{country}', [CoordinateController::class, 'citiesByCountry']);
    Route::get('coordinates/{id}/usage', [CoordinateController::class, 'usage']);
    Route::apiResource('coordinates', CoordinateController::class);

    // UPLOAD DE FICHIERS
    Route::post('/upload-image', [FileUploadController::class, 'uploadImage']);
    Route::delete('/delete-image', [FileUploadController::class, 'deleteImage']);

    // TAUX DE CHANGE
    Route::prefix('currency-rates')->group(function () {
        Route::get('current', [CurrencyRateController::class, 'current']);
        Route::post('convert', [CurrencyRateController::class, 'convert']);
    });
    Route::apiResource('currency-rates', CurrencyRateController::class);

    // TYPES DE COMPTES
    Route::apiResource('account-types', AccountTypeController::class);

    // CATÉGORIES DE DÉPENSES
    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // COMPTES MONÉTAIRES
    Route::get('accounts/cash-mobile-money', [AccountController::class, 'getCashMobileMoney']);
    Route::apiResource('accounts', AccountController::class)->except(['destroy']);
    Route::get('/accounts/{account}/transactions', [AccountTransactionController::class, 'index']);

    // TRANSACTIONS FINANCIÈRES
    Route::post('transactions/expense-operational', [AccountTransactionController::class, 'storeOperationalExpense']);
    Route::get('transactions/expense-operational', [AccountTransactionController::class, 'indexExpenseOperation']);
    Route::apiResource('transactions', AccountTransactionController::class)->only(['show', 'store']);
    Route::post('transactions/transfer', [AccountTransactionController::class, 'transfer']);
    Route::post('transactions/{transaction}/cancel', [AccountTransactionController::class, 'cancel']);

    // PAIEMENTS RÉAPPROVISIONNEMENT
    Route::prefix('stock-payments')->group(function () {
        Route::post('supplier', [StockPaymentController::class, 'paySupplier']);
        Route::post('freight', [StockPaymentController::class, 'payFreight']);
        Route::post('complete', [StockPaymentController::class, 'payComplete']);
        Route::get('receipt/{stock_receipt_id}', [StockPaymentController::class, 'getReceiptTransactions']);
    });

    // EMPLACEMENTS DE STOCKAGE
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{id}/can-delete', [LocationController::class, 'canDelete']);
    Route::get('locations/{id}/variants-detail', [LocationController::class, 'getVariantsDetail']);
    Route::get('locations/{id}/stock-movements', [LocationController::class, 'getStockMovements']);
    Route::get('locations/{id}/statistics', [LocationController::class, 'statistics']);
    Route::get('locations/{id}/reservations', [LocationController::class, 'getActiveReservations']);
    Route::get('locations-active', [LocationController::class, 'activeLocations']);
    Route::get('locations-warehouses', [LocationController::class, 'warehouses']);

    // RÉCEPTIONS DE STOCK
    Route::prefix('stock-receipts')->group(function () {
        Route::get('/global-statistics', [StockReceiptController::class, 'globalStatistics']);
        Route::get('/', [StockReceiptController::class, 'index']);
        Route::post('/', [StockReceiptController::class, 'store']);
        Route::get('/{stockReceipt}', [StockReceiptController::class, 'show']);
        Route::put('/{stockReceipt}', [StockReceiptController::class, 'update']);
        Route::post('/{stockReceipt}/mark-shipped', [StockReceiptController::class, 'markAsShipped']);
        Route::post('/{stockReceipt}/mark-in-transit', [StockReceiptController::class, 'markAsInTransit']);
        Route::post('/{stockReceipt}/mark-arrived', [StockReceiptController::class, 'markAsArrived']);
        Route::post('/{stockReceipt}/mark-rated', [StockReceiptController::class, 'markAsRated']);
        Route::post('/{stockReceipt}/validate', [StockReceiptController::class, 'validateReceipt']);
        Route::post('/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel']);
        Route::get('{stockReceipt}/cost-recommendations', [StockReceiptController::class, 'getCostRecommendations']);
        Route::post('{stockReceipt}/apply-costs', [StockReceiptController::class, 'applyCosts']);
        Route::post('/{stockReceipt}/move-received-variant', [StockReceiptController::class, 'moveReceivedQuantity']);
        Route::post('/{stockReceipt}/payment', [StockReceiptController::class, 'recordPayment']);
        Route::post('/{stockReceipt}/rate', [StockReceiptController::class, 'rateReceipt']);
        Route::get('/{stockReceipt}/cost-allocations', [StockReceiptController::class, 'getCostAllocated']);
        Route::get('/{stockReceipt}/statistics', [StockReceiptController::class, 'statistics']);
    });

    // MOUVEMENTS DE STOCK
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
        Route::post('loss', [StockMovementController::class, 'declareLoss']);
        Route::post('reconcile-inventory', [StockMovementController::class, 'reconcileInventory']);
    });

    // CLIENTS
    Route::prefix('customers')->group(function () {
        Route::get('/search-for-sale', [CustomerController::class, 'searchForSale']);
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::get('/{id}/sales-immediate', [CustomerController::class, 'getCustomerSaleImmediate']);
        Route::get('/{id}/credits', [CustomerController::class, 'getCustomerCredits']);
        Route::get('/{id}/reservations', [CustomerController::class, 'getCustomerReservations']);
    });

    // LOCALISATIONS DE VARIANTES
    Route::prefix('product-variant-locations')->group(function () {
        Route::get('/', [ProductVariantLocationController::class, 'index']);
        Route::post('/', [ProductVariantLocationController::class, 'store']);
        Route::get('/{id}', [ProductVariantLocationController::class, 'show']);
        Route::put('/{id}', [ProductVariantLocationController::class, 'update']);
        Route::delete('/{id}', [ProductVariantLocationController::class, 'destroy']);
        Route::get('/{id}/can-delete', [ProductVariantLocationController::class, 'canDelete']);
    });
    Route::get('locations/{locationId}/variants', [ProductVariantLocationController::class, 'getByLocation']);
    Route::get('locations/{locationId}/statistics', [ProductVariantLocationController::class, 'locationStatistics']);
    Route::get('variants/{variantId}/locations', [ProductVariantLocationController::class, 'getByVariant']);

    // VENTES
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/immediate', [ImmediateSaleController::class, 'index']);
        Route::get('/immediate/{id}', [ImmediateSaleController::class, 'show']);
        Route::get('/{id}', [SaleController::class, 'show']);
        Route::post('/immediate', [ImmediateSaleController::class, 'store']);
        Route::post('/credit', [CreditController::class, 'store']);
        Route::post('/reservation', [ReservationController::class, 'store']);
        Route::post('/immediate/cancel/{sale}', [ImmediateSaleController::class, 'cancel']);
    });

    // CRÉDITS
    Route::prefix('credits')->group(function () {
        Route::get('/', [CreditController::class, 'index']);
        Route::get('/{id}', [CreditController::class, 'show']);
        Route::post('/{creditId}/installments/{installmentId}/pay', [CreditController::class, 'payInstallment']);
        Route::post('/cancel/{credit}', [CreditController::class, 'cancel']);
    });

    // RÉSERVATIONS
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::get('/{id}', [ReservationController::class, 'show']);
        Route::post('/{id}/complete', [ReservationController::class, 'complete']);
        Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
    });

    // DÉPENSES PLANIFIÉES
    Route::prefix('planned-expenses')->group(function () {
        Route::get('/', [PlannedExpenseController::class, 'index']);
        Route::post('/', [PlannedExpenseController::class, 'store']);
        Route::get('/stats', [PlannedExpenseController::class, 'stats']);
        Route::get('/{plannedExpense}', [PlannedExpenseController::class, 'show']);
        Route::put('/{plannedExpense}', [PlannedExpenseController::class, 'update']);
        Route::post('/{plannedExpense}/mark-paid', [PlannedExpenseController::class, 'markPaid']);
        Route::delete('/{plannedExpense}', [PlannedExpenseController::class, 'destroy']);
        Route::get('/{plannedExpense}/transactions', [PlannedExpenseController::class, 'transactions']);
    });

    // STATISTIQUES DE VENTE
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

    // STATISTIQUES FINANCIÈRES
    Route::prefix('statistics/financial')->group(function () {
        Route::get('dashboard', [SalesStatisticsController::class, 'financialDashboard']);
        Route::get('timeline', [SalesStatisticsController::class, 'financialTimeline']);
        Route::get('profits', [SalesStatisticsController::class, 'profitsOverview']);
        Route::get('expenses', [SalesStatisticsController::class, 'expensesOverview']);
        Route::get('losses', [SalesStatisticsController::class, 'lossesOverview']);
    });

    // DASHBOARD
    Route::get('dashboard', [DashboardController::class, 'index']);

    // FACTURES PDF
    Route::prefix('invoices')->group(function () {
        Route::get('/{sale}/download-sale', [InvoiceController::class, 'downloadSale']);
        Route::get('/{credit}/download-credit', [InvoiceController::class, 'downloadCredit']);
        Route::get('/{installmentTransaction}/download-transaction-installment', [InvoiceController::class, 'downloadPaymentReceipt']);
        Route::get('/{reservation}/download-reservation', [InvoiceController::class, 'downloadReservation']);
        Route::get('/{reservation}/download-reservation-receipt', [InvoiceController::class, 'downloadReservationReceipt']);
        Route::get('/{cashCount}/download-cash-count', [InvoiceController::class, 'downloadCashCount']);
    });

    // COMPTAGE DE CAISSE
    Route::apiResource('cash-counts', CashCountController::class)->only(['index', 'store', 'show', 'update']);

    // INFOS ENTREPRISE
    Route::get('company-info', [CompanyInfoController::class, 'index']);
    Route::put('company-info', [CompanyInfoController::class, 'update']);

    // IMPRESSION THERMIQUE POS
    Route::prefix('print')->group(function () {
        Route::get('/test', [PrintController::class, 'testPrinter']);
        Route::get('/sale/{id}', [PrintController::class, 'printSale']);
        Route::get('/credit/{credit}', [PrintController::class, 'printCredit']);
        Route::get('/reservation/{reservation}', [PrintController::class, 'printReservation']);
        Route::get('/reservation-receipt/{reservation}', [PrintController::class, 'printReservationReceipt']);
        Route::get('/cash-count/{cashCount}', [PrintController::class, 'printCashCount']);
        Route::get('/installment-transaction/{installmentTransaction}', [PrintController::class, 'printInstallmentTransaction']);
    });

    // JOURNAUX D'ACTIVITÉ
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/statistics', [ActivityLogController::class, 'statistics']);
        Route::get('/failures', [ActivityLogController::class, 'failures']);
        Route::get('/actions-by-category', [ActivityLogController::class, 'actionsByCategory']);
        Route::get('/by-model', [ActivityLogController::class, 'byModel']);
        Route::get('/{activityLog}', [ActivityLogController::class, 'show']);

        Route::middleware(['role:admin'])->group(function () {
            Route::post('/delete-between-dates', [ActivityLogController::class, 'deleteBetweenDates']);
            Route::post('/delete-older-than', [ActivityLogController::class, 'deleteOlderThan']);
            Route::post('/delete-by-status', [ActivityLogController::class, 'deleteByStatus']);
            Route::post('/auto-cleanup', [ActivityLogController::class, 'autoCleanup']);
        });
    });

});

// NOTIFICATIONS
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
