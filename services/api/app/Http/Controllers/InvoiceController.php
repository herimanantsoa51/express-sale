<?php

namespace App\Http\Controllers;

use App\Models\CashCount;
use App\Models\Credit;
use App\Models\InstallmentTransaction;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Télécharger la facture en PDF
     */
    public function downloadSale(Sale $sale)
    {
        return $this->invoiceService->downloadSale($sale);
    }

    /**
     * Afficher la facture dans le navigateur
     */
    public function showSale(Sale $sale, Request $request)
    {
        return $this->invoiceService->streamSale($sale);
    }

    public function downloadCredit(Credit $credit)
    {
        return $this->invoiceService->downloadCredit($credit);
    }

    public function showCredit(Credit $credit, Request $request)
    {
        return $this->invoiceService->streamCredit($credit);
    }

    public function downloadPaymentReceipt(InstallmentTransaction $installmentTransaction)
    {
        return $this->invoiceService->downloadPaymentReceipt($installmentTransaction);
    }

    public function showPaymentReceipt(InstallmentTransaction $installmentTransaction, Request $request)
    {
        return $this->invoiceService->streamPaymentReceipt($installmentTransaction);
    }

    public function downloadReservation(Reservation $reservation)
    {
        return $this->invoiceService->downloadReservation($reservation);
    }

    public function showReservation(Reservation $reservation, Request $request)
    {
        return $this->invoiceService->streamReservation($reservation);
    }

    public function downloadReservationReceipt(Reservation $reservation)
    {
        return $this->invoiceService->downloadReservationReceipt($reservation);
    }

    public function showReservationReceipt(Reservation $reservation, Request $request)
    {
        return $this->invoiceService->streamReservationReceipt($reservation);
    }

    public function downloadCashCount(CashCount $cashCount)
    {
        return $this->invoiceService->downloadCashCount($cashCount);
    }

    public function showCashCount(CashCount $cashCount, Request $request)
    {
        return $this->invoiceService->streamCashCount($cashCount);
    }
}
