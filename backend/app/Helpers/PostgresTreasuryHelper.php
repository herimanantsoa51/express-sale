<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Helper pour utiliser les fonctions PostgreSQL de trésorerie
 */
class PostgresTreasuryHelper
{
    /**
     * Crée un compte avec solde initial en utilisant la fonction PostgreSQL
     */
    public static function createAccountWithInitialBalance(
        int $accountTypeId,
        string $name,
        ?string $accountNumber,
        float $initialBalance,
        ?string $notes,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT create_account_with_initial_balance(?, ?, ?, ?, ?, ?) as account_id',
            [
                $accountTypeId,
                $name,
                $accountNumber,
                $initialBalance,
                $notes,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors de la création du compte');
        }

        return $result[0]->account_id;
    }

    /**
     * Enregistre un paiement de fournisseur
     */
    public static function recordSupplierPayment(
        int $accountId,
        int $supplierId,
        int $stockReceiptId,
        float $amount,
        ?string $referenceNumber,
        ?string $notes,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_supplier_payment(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $accountId,
                $supplierId,
                $stockReceiptId,
                $amount,
                $referenceNumber,
                $notes,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors du paiement fournisseur');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre un paiement de transitaire
     */
    public static function recordFreightPayment(
        int $accountId,
        int $freightForwarderId,
        int $stockReceiptId,
        float $amount,
        ?string $referenceNumber,
        ?string $notes,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_freight_payment(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $accountId,
                $freightForwarderId,
                $stockReceiptId,
                $amount,
                $referenceNumber,
                $notes,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors du paiement transitaire');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre une dépense opérationnelle
     */
    public static function recordOperatingExpense(
        int $accountId,
        int $expenseCategoryId,
        float $amount,
        ?string $recipientName,
        string $description,
        ?string $notes,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_operating_expense(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $accountId,
                $expenseCategoryId,
                $amount,
                $recipientName,
                $description,
                $notes,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors de l\'enregistrement de la dépense');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre un transfert entre comptes
     */
    public static function recordAccountTransfer(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        ?string $description,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_account_transfer(?, ?, ?, ?, ?) as transaction_id',
            [
                $fromAccountId,
                $toAccountId,
                $amount,
                $description,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors du transfert');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre le paiement d'une vente directe
     * Cette fonction gère automatiquement les frais mobile money
     */
    public static function recordDirectSalePayment(
        int $saleId,
        int $accountId,
        string $paymentMethod,
        float $amount,
        float $cashAmount = 0,
        float $mobileMoney = 0,
        ?string $mobileMoneyNumber = null,
        float $mobileMoneyFees = 0,
        float $bankTransfer = 0,
        ?string $referenceNumber = null,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_direct_sale_payment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $saleId,
                $accountId,
                $paymentMethod,
                $amount,
                $cashAmount,
                $mobileMoney,
                $mobileMoneyNumber,
                $mobileMoneyFees,
                $bankTransfer,
                $referenceNumber,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors de l\'enregistrement du paiement');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre l'acompte d'une réservation
     */
    public static function recordReservationDeposit(
        int $reservationId,
        int $saleId,
        int $accountId,
        float $depositAmount,
        string $paymentMethod,
        ?string $referenceNumber,
        int $createdBy
    ): ?int {
        $result = DB::select(
            'SELECT record_reservation_deposit(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $reservationId,
                $saleId,
                $accountId,
                $depositAmount,
                $paymentMethod,
                $referenceNumber,
                $createdBy
            ]
        );

        return $result[0]->transaction_id ?? null;
    }

    /**
     * Finalise le paiement d'une réservation
     */
    public static function completeReservationPayment(
        int $reservationId,
        int $saleId,
        int $accountId,
        float $remainingAmount,
        string $paymentMethod,
        ?string $referenceNumber,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT complete_reservation_payment(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $reservationId,
                $saleId,
                $accountId,
                $remainingAmount,
                $paymentMethod,
                $referenceNumber,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors de la finalisation du paiement');
        }

        return $result[0]->transaction_id;
    }

    /**
     * Enregistre l'acompte initial d'une vente à crédit
     */
    public static function recordCreditSaleInitialPayment(
        int $saleId,
        int $creditId,
        int $accountId,
        float $amountPaid,
        string $paymentMethod,
        ?string $referenceNumber,
        int $createdBy
    ): ?int {
        $result = DB::select(
            'SELECT record_credit_sale_initial_payment(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $saleId,
                $creditId,
                $accountId,
                $amountPaid,
                $paymentMethod,
                $referenceNumber,
                $createdBy
            ]
        );

        return $result[0]->transaction_id ?? null;
    }

    /**
     * Enregistre un paiement d'échéance de crédit
     */
    public static function recordCreditPayment(
        int $creditInstallmentId,
        int $accountId,
        float $amountPaid,
        string $paymentMethod,
        ?string $referenceNumber,
        ?string $notes,
        int $createdBy
    ): int {
        $result = DB::select(
            'SELECT record_credit_payment(?, ?, ?, ?, ?, ?, ?) as transaction_id',
            [
                $creditInstallmentId,
                $accountId,
                $amountPaid,
                $paymentMethod,
                $referenceNumber,
                $notes,
                $createdBy
            ]
        );

        if (empty($result)) {
            throw new \Exception('Erreur lors du paiement du crédit');
        }

        return $result[0]->transaction_id;
    }
}