<?php

namespace App\Http\Controllers;

use App\Models\CashCount;
use App\Models\Credit;
use App\Models\InstallmentTransaction;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\PosPrintService;

class PrintController extends Controller
{
    private $posPrintService;

    public function __construct()
    {
        $this->posPrintService = new PosPrintService;
    }

    /**
     * Imprimer une vente immédiate
     */
    public function printSale($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $result = $this->posPrintService->printSale($sale);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printReservation(Reservation $reservation)
    {
        try {
            return response()->json($this->posPrintService->printReservation($reservation));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printReservationReceipt(Reservation $reservation)
    {
        try {
            return response()->json($this->posPrintService->printReservationReceipt($reservation));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printCredit(Credit $credit)
    {
        try {
            return response()->json($this->posPrintService->printCredit($credit));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printInstallmentTransaction(InstallmentTransaction $installmentTransaction)
    {
        try {
            return response()->json($this->posPrintService->printCreditPayment($installmentTransaction));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printCashCount(CashCount $cashCount)
    {
        try {
            return response()->json($this->posPrintService->printCashCount($cashCount));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tester l'imprimante
     */
    public function testPrinter()
    {
        try {
            $this->posPrintService->testPrinter();

            return response()->json([
                'success' => true,
                'message' => 'Test d\'impression envoyé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
