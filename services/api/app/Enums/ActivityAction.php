<?php

namespace App\Enums;

class ActivityAction
{
    const CASH_COUNT_CREATED = 'cash_count_created';

    const CASH_COUNT_UPDATED = 'cash_count_updated';

    // Ventes
    const SALE_CREATED = 'sale_created';

    const SALE_CANCELLED = 'sale_cancelled';

    const FILE_UPLOADED = 'file_uploaded';

    // Crédits
    const CREDIT_CREATED = 'credit_created';

    const CREDIT_CANCELLED = 'credit_cancelled';

    const INSTALLMENT_PAID = 'installment_paid';

    const SUPPLIER_CREATED = 'supplier_created';

    const SUPPLIER_UPDATED = 'supplier_updated';

    const SUPPLIER_DELETED = 'supplier_deleted';

    // Réservations
    const RESERVATION_CREATED = 'reservation_created';

    const RESERVATION_COMPLETED = 'reservation_completed';

    const RESERVATION_CANCELLED = 'reservation_cancelled';

    const RESERVATION_DEPOSIT_ADDED = 'reservation_deposit_added';

    // Stock
    const STOCK_RECEIPT_CREATED = 'stock_receipt_created';

    const STOCK_RECEIPT_VALIDATED = 'stock_receipt_validated';

    const STOCK_RECEIPT_CANCELLED = 'stock_receipt_cancelled';

    const STOCK_RECEIPT_RATED = 'stock_receipt_rated';

    const STOCK_RECEIPT_COST_DISTRIBUTED = 'stock_receipt_cost_distributed';

    const STOCK_RECEIPT_ARRIVED = 'stock_receipt_arrived';

    const STOCK_TRANSFERRED = 'stock_transferred';

    const STOCK_RECEIPT_IN_TRANSIT = 'stock_receipt_in_transit';

    const STOCK_ADJUSTED = 'stock_adjusted';

    const STOCK_LOSS_DECLARED = 'stock_loss_declared';

    const STOCK_RECEIPT_UPDATED = 'stock_receipt_updated';

    const STOCK_RECEIPT_SHIPPED = 'stock_receipt_shipped';

    const STOCK_RECEIPT_PAYMENT = 'stock_receipt_payment';

    // Paiements
    const SUPPLIER_PAID = 'supplier_paid';

    const FREIGHT_PAID = 'freight_paid';

    // Produits
    const PRODUCT_CREATED = 'product_created';

    const PRODUCT_UPDATED = 'product_updated';

    const PRODUCT_DELETED = 'product_deleted';

    const PRODUCT_PRICES_UPDATED = 'product_prices_updated';

    // Clients
    const CUSTOMER_CREATED = 'customer_created';

    const CUSTOMER_UPDATED = 'customer_updated';

    const CUSTOMER_CREDIT_LIMIT_ADJUSTED = 'customer_credit_limit_adjusted';

    const CUSTOMER_LOYALTY_POINTS_ADDED = 'customer_loyalty_points_added';

    // Comptes
    const ACCOUNT_CREATED = 'account_created';

    const ACCOUNT_ACTIVATED = 'account_activated';

    const ACCOUNT_DEACTIVATED = 'account_deactivated';

    const ACCOUNT_TRANSACTION_TRANSFER = 'account_transaction_transfer';

    const ACCOUNT_UPDATED = 'account_updated';

    const ACCOUNT_TRANSACTION_CREATED = 'account_transaction_created';

    const ACCOUNT_TRANSACTION_CANCELLED = 'account_transaction_cancelled';

    // Transactions
    const EXPENSE_CREATED = 'expense_created';

    const TRANSACTION_CANCELLED = 'transaction_cancelled';

    // Utilisateurs
    const USER_CREATED = 'user_created';

    const USER_UPDATED = 'user_updated';

    const USER_STATUS_TOGGLED = 'user_status_toggled';

    // Authentification
    const LOGIN_SUCCESS = 'login_success';

    const LOGIN_FAILED = 'login_failed';

    const LOGOUT = 'logout';

    const CATEGORY_CREATED = 'category_created';

    const CATEGORY_UPDATED = 'category_updated';

    const CATEGORY_DELETED = 'category_deleted';

    const PRODUCT_VARIANT_CREATED = 'product_variant_created';

    const PRODUCT_VARIANT_UPDATED = 'product_variant_updated';

    const PRODUCT_VARIANT_DELETED = 'product_variant_deleted';

    const FREIGHT_FORWARDER_CREATED = 'freight_forwarder_created';

    const FREIGHT_FORWARDER_UPDATED = 'freight_forwarder_updated';

    const FREIGHT_FORWARDER_DELETED = 'freight_forwarder_deleted';

    const FILE_DELETED = 'file_deleted';

    const CURRENCY_RATE_UPDATED = 'currency_rate_updated';

    const CURRENCY_RATE_CREATED = 'currency_rate_created';

    const CURRENCY_RATE_DELETED = 'currency_rate_deleted';

    const EXPENSE_CATEGORY_CREATED = 'expense_category_created';

    public static function all(): array
    {
        return [
            self::SALE_CREATED,
            self::SALE_CANCELLED,
            self::CREDIT_CREATED,
            self::CREDIT_CANCELLED,
            self::INSTALLMENT_PAID,
            self::RESERVATION_CREATED,
            self::RESERVATION_COMPLETED,
            self::RESERVATION_CANCELLED,
            self::RESERVATION_DEPOSIT_ADDED,
            self::STOCK_RECEIPT_CREATED,
            self::STOCK_RECEIPT_VALIDATED,
            self::STOCK_RECEIPT_CANCELLED,
            self::STOCK_RECEIPT_ARRIVED,
            self::STOCK_RECEIPT_COST_DISTRIBUTED,
            self::STOCK_RECEIPT_RATED,
            self::STOCK_RECEIPT_IN_TRANSIT,
            self::STOCK_TRANSFERRED,
            self::STOCK_RECEIPT_SHIPPED,
            self::STOCK_ADJUSTED,
            self::STOCK_RECEIPT_UPDATED,
            self::STOCK_LOSS_DECLARED,
            self::STOCK_RECEIPT_IN_TRANSIT,
            self::SUPPLIER_PAID,
            self::FREIGHT_PAID,
            self::PRODUCT_CREATED,
            self::PRODUCT_UPDATED,
            self::PRODUCT_DELETED,
            self::PRODUCT_VARIANT_CREATED,
            self::PRODUCT_VARIANT_UPDATED,
            self::PRODUCT_VARIANT_DELETED,
            self::PRODUCT_PRICES_UPDATED,
            self::CUSTOMER_CREATED,
            self::CUSTOMER_UPDATED,
            self::CUSTOMER_CREDIT_LIMIT_ADJUSTED,
            self::CUSTOMER_LOYALTY_POINTS_ADDED,
            self::ACCOUNT_CREATED,
            self::ACCOUNT_ACTIVATED,
            self::ACCOUNT_DEACTIVATED,
            self::ACCOUNT_TRANSACTION_TRANSFER,
            self::EXPENSE_CREATED,
            self::TRANSACTION_CANCELLED,
            self::USER_CREATED,
            self::USER_UPDATED,
            self::USER_STATUS_TOGGLED,
            self::LOGIN_SUCCESS,
            self::LOGIN_FAILED,
            self::LOGOUT,
            self::CATEGORY_CREATED,
            self::CATEGORY_UPDATED,
            self::CATEGORY_DELETED,
            self::SUPPLIER_CREATED,
            self::SUPPLIER_UPDATED,
            self::SUPPLIER_DELETED,
            self::FREIGHT_FORWARDER_CREATED,
            self::FREIGHT_FORWARDER_UPDATED,
            self::FREIGHT_FORWARDER_DELETED,
            self::FILE_UPLOADED,
            self::FILE_DELETED,
            self::CURRENCY_RATE_UPDATED,
            self::CURRENCY_RATE_CREATED,
            self::CURRENCY_RATE_DELETED,
            self::EXPENSE_CATEGORY_CREATED,
            self::ACCOUNT_CREATED,
            self::ACCOUNT_UPDATED,
            self::ACCOUNT_TRANSACTION_CREATED,
            self::ACCOUNT_TRANSACTION_CANCELLED,

        ];
    }

    public static function labels(): array
    {
        return [
            self::SALE_CREATED => 'Vente créée',
            self::SALE_CANCELLED => 'Vente annulée',
            self::CREDIT_CREATED => 'Crédit créé',
            self::CREDIT_CANCELLED => 'Crédit annulé',
            self::INSTALLMENT_PAID => 'Échéance payée',
            self::RESERVATION_CREATED => 'Réservation créée',
            self::RESERVATION_COMPLETED => 'Réservation complétée',
            self::RESERVATION_CANCELLED => 'Réservation annulée',
            self::STOCK_RECEIPT_CREATED => 'Réapprovisionnement créé',
            self::STOCK_RECEIPT_VALIDATED => 'Réapprovisionnement validé',
            self::STOCK_RECEIPT_ARRIVED => 'Réapprovisionnement arrivé',
            self::STOCK_RECEIPT_CANCELLED => 'Réapprovisionnement annulé',
            self::STOCK_RECEIPT_RATED => 'Réapprovisionnement évalué',
            self::STOCK_RECEIPT_COST_DISTRIBUTED => 'Réapprovisionnement cout distribué',
            self::STOCK_RECEIPT_SHIPPED => 'Réapprovisionnement marqué envoyé',
            self::STOCK_RECEIPT_IN_TRANSIT => 'Réapprovisionnement marqué en transit',
            self::STOCK_RECEIPT_PAYMENT => 'Paiement de Réapprovisionnement',
            self::STOCK_TRANSFERRED => 'Stock transféré',
            self::STOCK_RECEIPT_UPDATED => 'Réapprovisionnement mis à jour',
            self::LOGIN_SUCCESS => 'Connexion réussie',
            self::LOGIN_FAILED => 'Tentative de connexion échouée',
            self::LOGOUT => 'Déconnexion',
            self::PRODUCT_CREATED => 'Produit créé',
            self::PRODUCT_UPDATED => 'Produit modifié',
            self::PRODUCT_DELETED => 'Produit supprimé',
            self::PRODUCT_PRICES_UPDATED => 'Prix mis à jour en masse',
            self::CUSTOMER_CREATED => 'Client créé',
            self::CUSTOMER_UPDATED => 'Client modifié',
            self::USER_CREATED => 'Utilisateur créé',
            self::USER_UPDATED => 'Utilisateur modifié',
            self::USER_STATUS_TOGGLED => "Statut d'utilisateur modifié",
            self::CATEGORY_CREATED => 'Catégorie créée',
            self::CATEGORY_UPDATED => 'Catégorie modifiée',
            self::CATEGORY_DELETED => 'Catégorie supprimée',
            self::PRODUCT_VARIANT_CREATED => 'Variante de produit créée',
            self::PRODUCT_VARIANT_UPDATED => 'Variante de produit modifiée',
            self::PRODUCT_VARIANT_DELETED => 'Variante de produit supprimée',
            self::SUPPLIER_CREATED => 'Fournisseur créé',
            self::SUPPLIER_UPDATED => 'Fournisseur modifié',
            self::SUPPLIER_DELETED => 'Fournisseur supprimé',
            self::FREIGHT_FORWARDER_CREATED => 'Transitaire créé',
            self::FREIGHT_FORWARDER_UPDATED => 'Transitaire modifié',
            self::FREIGHT_FORWARDER_DELETED => 'Transitaire supprimé',
            self::FILE_UPLOADED => 'Fichier uploadé',
            self::FILE_DELETED => 'Fichier supprimé',
            self::CURRENCY_RATE_UPDATED => 'Taux de change mis à jour',
            self::CURRENCY_RATE_CREATED => 'Taux de change créé',
            self::CURRENCY_RATE_DELETED => 'Taux de change supprimé',
            self::EXPENSE_CATEGORY_CREATED => 'Catégorie de dépense créée',
            self::ACCOUNT_CREATED => 'Compte créé',
            self::ACCOUNT_UPDATED => 'Compte mis à jour',
            self::ACCOUNT_TRANSACTION_CREATED => 'Transaction de compte créée',
            self::ACCOUNT_TRANSACTION_CANCELLED => 'Transaction de compte annulée',
            self::ACCOUNT_TRANSACTION_TRANSFER => 'Transaction de transfert de fonds créé',

            // ... etc
        ];
    }
}
