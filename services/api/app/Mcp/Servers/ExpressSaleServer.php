<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AllocateStockReceiptCostsTool;
use App\Mcp\Tools\CancelCreditTool;
use App\Mcp\Tools\CancelReservationTool;
use App\Mcp\Tools\CancelSaleTool;
use App\Mcp\Tools\CancelTransactionTool;
use App\Mcp\Tools\CompleteReservationTool;
use App\Mcp\Tools\CreateAttributeValueTool;
use App\Mcp\Tools\CreateCategoryTool;
use App\Mcp\Tools\CreateCreditSaleTool;
use App\Mcp\Tools\CreateCustomerTool;
use App\Mcp\Tools\CreateExpenseTool;
use App\Mcp\Tools\CreateFreightForwarderTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreateProductVariantTool;
use App\Mcp\Tools\CreateReservationTool;
use App\Mcp\Tools\CreateSaleTool;
use App\Mcp\Tools\CreateStockReceiptTool;
use App\Mcp\Tools\CreateSupplierTool;
use App\Mcp\Tools\CurrencyTool;
use App\Mcp\Tools\DashboardTool;
use App\Mcp\Tools\DeclareStockLossTool;
use App\Mcp\Tools\FinancialReportTool;
use App\Mcp\Tools\FinancialTimelineTool;
use App\Mcp\Tools\GetProductTool;
use App\Mcp\Tools\ListAccountsTool;
use App\Mcp\Tools\ListAccountTypesTool;
use App\Mcp\Tools\ListActivityLogsTool;
use App\Mcp\Tools\ListAttributeTypesTool;
use App\Mcp\Tools\ListCategoriesTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\ListCustomerHistoryTool;
use App\Mcp\Tools\ListExpenseCategoriesTool;
use App\Mcp\Tools\ListFreightForwardersTool;
use App\Mcp\Tools\ListLocationsTool;
use App\Mcp\Tools\ListLowStockTool;
use App\Mcp\Tools\ListSalesTool;
use App\Mcp\Tools\ListStockReceiptsTool;
use App\Mcp\Tools\ListSuppliersTool;
use App\Mcp\Tools\ListTransactionsTool;
use App\Mcp\Tools\ManageAccountTool;
use App\Mcp\Tools\ManageCashCountTool;
use App\Mcp\Tools\ManageCompanyInfoTool;
use App\Mcp\Tools\ManageImageTool;
use App\Mcp\Tools\ManageLocationTool;
use App\Mcp\Tools\ManagePlannedExpenseTool;
use App\Mcp\Tools\PayCreditInstallmentTool;
use App\Mcp\Tools\PayStockReceiptTool;
use App\Mcp\Tools\RateStockReceiptTool;
use App\Mcp\Tools\SalesReportTool;
use App\Mcp\Tools\SalesStatisticsTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\GenerateDocumentTool;
use App\Mcp\Tools\SendDocumentEmailTool;
use App\Mcp\Tools\StockAdjustmentTool;
use App\Mcp\Tools\StockMovementsReportTool;
use App\Mcp\Tools\StockTransferTool;
use App\Mcp\Tools\TransferFundsTool;
use App\Mcp\Tools\UpdateCustomerTool;
use App\Mcp\Tools\UpdateProductTool;
use App\Mcp\Tools\UpdateProductVariantTool;
use App\Mcp\Tools\UpdateStockReceiptStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Express Sale Server')]
#[Version('2.1.0')]
#[Instructions('Ce serveur expose la boutique Express Sale en lecture et en écriture — ventes, catalogue, stock, achats, finance. '.
    'Lecture : catalogue produits, catégories, attributs, clients (avec historique), emplacements et comptes de trésorerie, ventes, commandes d\'achat, transactions, '.
    'rapports de ventes détaillés, statistiques financières, dashboard général, traçabilité des mouvements de stock, journaux d\'activité et taux de change. '.
    'Écriture : produits (création/modification, variantes avec attributs), clients, ventes (comptant, crédit, réservation) avec cycle de vie complet, '.
    'stock (transferts, ajustements, pertes, emplacements), achats (fournisseurs, transitaires, commandes, paiements, répartition des coûts), '.
    'finance (dépenses, transferts, annulation de transaction, comptes, dépenses planifiées, comptage de caisse, coordonnées boutique). '.
    'Documents et communication : gestion des images (upload base64, suppression), génération de factures et reçus PDF (base64 ou stockés), '.
    'envoi par email avec pièce jointe (configurer MAIL_* dans .env avec un vrai SMTP, sinon journalisation). '.
    'Exemple : après une vente ou un paiement, demander au client son email puis envoyer la facture via send-document-email-tool. '.
    'IMPORTANT — politique d\'annulation : annuler une vente, un crédit ou une réservation marque UNIQUEMENT le statut comme annulé. '.
    'Aucun remboursement automatique ni remise en stock : l\'opérateur régularise manuellement l\'argent avec cancel-transaction-tool '.
    '(une transaction à la fois) et le stock avec stock-adjustment-tool. Signaler systématiquement ces régularisations manuelles à faire. '.
    'Workflow d\'achat complet : 1) list-low-stock-tool pour identifier les besoins, 2) list-suppliers-tool et list-freight-forwarders-tool pour les partenaires, '.
    '3) create-stock-receipt-tool pour passer la commande, 4) pay-stock-receipt-tool pour payer (fournisseur et/ou transitaire), '.
    '5) update-stock-receipt-status-tool à chaque étape logistique (envoyée, en transit, arrivée avec quantités reçues), '.
    '6) rate-stock-receipt-tool (évaluation qualité, crée les batches) puis allocate-stock-receipt-costs-tool (recommend puis apply) pour répartir transport et frais, '.
    '7) update-stock-receipt-status-tool action=validated avec les emplacements de stockage. '.
    'Workflow pour créer un produit : 1) list-categories-tool et list-attribute-types-tool, 2) create-product-tool, '.
    '3) create-product-variant-tool avec les attributs (créer les valeurs manquantes via create-attribute-value-tool). '.
    'Workflow recommandé pour vendre : 1) list-locations-tool et list-accounts-tool pour découvrir les identifiants, '.
    '2) search-products-tool pour choisir les variantes en stock (consulter list-customer-history-tool avant un crédit), '.
    '3) create-sale-tool / create-credit-sale-tool / create-reservation-tool, '.
    '4) le cas échéant complete-reservation-tool ou pay-credit-installment-tool pour encaisser le solde, '.
    'ou cancel-reservation-tool / cancel-sale-tool / cancel-credit-tool pour annuler.')]
class ExpressSaleServer extends Server
{
    protected array $tools = [
        // Lecture — ventes, clients, catalogue
        SearchProductsTool::class,
        GetProductTool::class,
        ListCustomersTool::class,
        ListCustomerHistoryTool::class,
        ListCategoriesTool::class,
        ListAttributeTypesTool::class,
        ListLocationsTool::class,
        ListAccountsTool::class,
        ListAccountTypesTool::class,
        ListSalesTool::class,
        ListStockReceiptsTool::class,
        ListLowStockTool::class,
        ListSuppliersTool::class,
        ListFreightForwardersTool::class,
        ListTransactionsTool::class,
        ListExpenseCategoriesTool::class,
        SalesReportTool::class,
        SalesStatisticsTool::class,
        FinancialReportTool::class,
        FinancialTimelineTool::class,
        DashboardTool::class,
        StockMovementsReportTool::class,
        ListActivityLogsTool::class,
        CurrencyTool::class,
        // Écriture — catalogue et clients
        CreateProductTool::class,
        CreateProductVariantTool::class,
        UpdateProductTool::class,
        UpdateProductVariantTool::class,
        CreateCategoryTool::class,
        CreateAttributeValueTool::class,
        CreateCustomerTool::class,
        UpdateCustomerTool::class,
        // Écriture — ventes
        CreateSaleTool::class,
        CreateCreditSaleTool::class,
        CreateReservationTool::class,
        CompleteReservationTool::class,
        CancelReservationTool::class,
        PayCreditInstallmentTool::class,
        CancelSaleTool::class,
        CancelCreditTool::class,
        // Écriture — stock
        CreateStockReceiptTool::class,
        UpdateStockReceiptStatusTool::class,
        RateStockReceiptTool::class,
        AllocateStockReceiptCostsTool::class,
        StockTransferTool::class,
        StockAdjustmentTool::class,
        DeclareStockLossTool::class,
        ManageLocationTool::class,
        // Écriture — achats
        CreateSupplierTool::class,
        CreateFreightForwarderTool::class,
        PayStockReceiptTool::class,
        // Écriture — finance
        CreateExpenseTool::class,
        TransferFundsTool::class,
        CancelTransactionTool::class,
        ManageAccountTool::class,
        ManagePlannedExpenseTool::class,
        ManageCashCountTool::class,
        ManageCompanyInfoTool::class,
        // Documents, médias, communication
        ManageImageTool::class,
        GenerateDocumentTool::class,
        SendDocumentEmailTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
