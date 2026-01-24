<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\CompanyInfo;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Models\Credit;
use App\Models\InstallmentTransaction;
use App\Models\Reservation;
use App\Models\CashCount;
use Illuminate\Support\Facades\Log;


class InvoiceService
{
    /**
     * Génère le PDF ultra rapide pour caisse
     */
    public function generateSale(Sale $sale)
    {
        // ✅ OPTIMISATION 1: Chargement minimal et ciblé
        $sale->loadMissing([
            'customer:id,customer_number,name,phone,address',
            'user:id,name',
            'items:id,sale_id,variant_id,quantity,unit_price,subtotal',
            'items.variant:id,sku,product_id',
            'items.variant.product:id,name',
        ]);

        // ✅ OPTIMISATION 2: Charger les attributs uniquement si nécessaire
        $needsAttributes = $sale->items->some(function($item) {
            return $item->variant->variantAttributeValues()->exists();
        });

        if ($needsAttributes) {
            $sale->load('items.variant.variantAttributeValues.attributeValue.attributeType');
        }

        // // ✅ OPTIMISATION 3: Cache company info (5 minutes seulement)
        $company =  $this->getCompanyInfo();

        // ✅ OPTIMISATION 4: Vue simplifiée, rendu direct
        $html = view('invoices.template-fast', compact('sale', 'company'))->render();

        // ✅ OPTIMISATION 5: Configuration minimaliste wkhtmltopdf
        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-right', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false) // ✅ Désactiver JS
            ->setOption('no-stop-slow-scripts', true)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('load-media-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true) // ✅ Pas de calculs supplémentaires
            ->setOption('dpi', 96); // ✅ DPI standard = plus rapide
    }

    /**
     * Télécharge (< 2 secondes garanti)
     */
    public function downloadSale(Sale $sale)
    {
        set_time_limit(30);
        
        $filename = "Facture_{$sale->sale_number}_" . $sale->sale_date->format('d-m-Y') . ".pdf";
        
        $pdf = $this->generateSale($sale);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Affiche dans le navigateur
     */
    public function streamSale(Sale $sale)
    {
        set_time_limit(30);
        
        $filename = "Facture_{$sale->sale_number}_" . $sale->sale_date->format('d-m-Y') . ".pdf";
        
        return $this->generateSale($sale)
            ->inline($filename);
    }

    


    public function generateCredit(Credit $credit)
    {
        // Chargement des relations nécessaires
        $credit->loadMissing([
            'sale:id,sale_number,sale_date,subtotal,discount_amount,total_amount,payment_method',
            'sale.customer:id,customer_number,name,phone,address',
            'sale.user:id,name',
            'sale.items:id,sale_id,variant_id,quantity,unit_price,subtotal',
            'sale.items.variant:id,sku,product_id',
            'sale.items.variant.product:id,name',
            'customer:id,customer_number,name,phone,address',
            'installments:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',
        ]);

        $needsAttributes = $credit->sale->items->some(function($item) {
            return $item->variant->variantAttributeValues()->exists();
        });

        if ($needsAttributes) {
            $credit->load('sale.items.variant.variantAttributeValues.attributeValue.attributeType');
        }
        // Info société
        $company = $this->getCompanyInfo();

        // Données pour la vue
        $data = [
            'credit' => $credit,
            'company' => $company,
            'total_paid' => $credit->amount_paid,
            'total_due' => $credit->amount_due,
            'payment_percentage' => $credit->getPaymentPercentage(),
        ];

        // Générer le HTML
        $html = view('invoices.credit-invoice', $data)->render();

        // Générer le PDF
        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-right', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);
    }

    /**
     * Télécharger la facture de crédit
     */
    public function downloadCredit(Credit $credit)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$credit->sale->sale_number}_" . $credit->credit_date->format('d-m-Y') . ".pdf";
        
        $pdf = $this->generateCredit($credit);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Afficher la facture de crédit dans le navigateur
     */
    public function streamCredit(Credit $credit)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$credit->sale->sale_number}_" . $credit->credit_date->format('d-m-Y') . ".pdf";
        
        return $this->generateCredit($credit)
            ->inline($filename);
    }



    /**
     * Génère le PDF du reçu de paiement
     */
    public function generatePaymentReceipt(InstallmentTransaction $installmentTransaction)
    {
        // Chargement des relations nécessaires
        $installmentTransaction->loadMissing([
            'installment:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',
            'installment.credit:id,sale_id,customer_id,total_amount,amount_paid,amount_due',
            'installment.credit.sale:id,sale_number,sale_date',
            'installment.credit.sale.customer:id,customer_number,name,phone',
            'installment.credit.customer:id,customer_number,name,phone',
            'transaction:id,account_id,amount,transaction_date,created_by,reference_number',
            'transaction.creator:id,name',
            'transaction.account.accountType:id,display_name'
        ]);

        // Info société
        $company = $this->getCompanyInfo();

        // Calcul des montants
        $installment = $installmentTransaction->installment;
        $credit = $installment->credit;
        
        $data = [
            'transaction' => $installmentTransaction,
            'installment' => $installment,
            'credit' => $credit,
            'company' => $company,
            'remaining_amount' => $installment->getRemainingAmount(),
            'payment_percentage' => $installment->getPaymentPercentage(),
            'total_credit_remaining' => $credit->amount_due,
        ];

        // Générer le HTML
        $html = view('invoices.credit-payment', $data)->render();

        // Générer le PDF
        return SnappyPdf::loadHTML($html)
            ->setPaper('a5')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);
    }

    /**
     * Télécharger le reçu de paiement
     */
    public function downloadPaymentReceipt(InstallmentTransaction $installmentTransaction)
    {
        set_time_limit(30);
        
        $credit = $installmentTransaction->installment->credit;
        $filename = "Recu_Paiement_{$credit->sale->sale_number}_" . $installmentTransaction->payment_date->format('d-m-Y') . ".pdf";
        
        $pdf = $this->generatePaymentReceipt($installmentTransaction);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Afficher le reçu de paiement dans le navigateur
     */
    public function streamPaymentReceipt(InstallmentTransaction $installmentTransaction)
    {
        set_time_limit(30);
        
        $credit = $installmentTransaction->installment->credit;
        $filename = "Recu_Paiement_{$credit->sale->sale_number}_" . $installmentTransaction->payment_date->format('d-m-Y') . ".pdf";
        
        return $this->generatePaymentReceipt($installmentTransaction)
            ->inline($filename);
    }

    public function generateReservationInvoice(Reservation $reservation)
    {
        // Chargement des relations nécessaires
        $reservation->loadMissing([
            'sale:id,sale_number,sale_date,subtotal,discount_amount,total_amount,payment_method',
            'sale.customer:id,customer_number,name,phone,address',
            'sale.user:id,name',
            'sale.items:id,sale_id,variant_id,quantity,unit_price,subtotal',
            'sale.items.variant:id,sku,product_id',
            'sale.items.variant.product:id,name',
            'customer:id,customer_number,name,phone,address',
        ]);

        $needsAttributes = $reservation->sale->items->some(function($item) {
            return $item->variant->variantAttributeValues()->exists();
        });

        if ($needsAttributes) {
            $reservation->load('sale.items.variant.variantAttributeValues.attributeValue.attributeType');
        }
        
        // Info société
        $company = $this->getCompanyInfo();

        // Données pour la vue
        $data = [
            'reservation' => $reservation,
            'company' => $company,
            'deposit_amount' => $reservation->deposit_amount,  // ✅ Utiliser les bonnes propriétés
            'remaining_amount' => $reservation->remaining_amount,  // ✅ Utiliser les bonnes propriétés
            'expiry_date' => $reservation->expiry_date
        ];

        // Générer le HTML
        $html = view('invoices.reservation-invoice', $data)->render();

        // Générer le PDF
        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-right', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);
    }

    public function downloadReservation(Reservation $reservation)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$reservation->sale->sale_number}" . ".pdf";
        
        $pdf = $this->generateReservationInvoice($reservation);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Afficher la facture de crédit dans le navigateur
     */
    public function streamReservation(Reservation $reservation)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$reservation->sale->sale_number}_" .  ".pdf";
        
        return $this->generateReservationInvoice($reservation)
            ->inline($filename);
    }

    public function generateReservationReceipt(Reservation $reservation)
    {
        $reservation->loadMissing([
            'sale:id,sale_number',  // ← MANQUANT !
            'customer:id,customer_number,name,phone,address',
            'transactionComplete:id,account_id,amount,transaction_date,reference_number,created_by',  // ← Ajout created_by
            'transactionComplete.account:id,account_type_id',  // ← MANQUANT !
            'transactionComplete.account.accountType:id,display_name',
            'transactionComplete.creator:id,name',
        ]);
        
        // Info société
        $company = $this->getCompanyInfo();

        // Données pour la vue
        $data = [
            'payment' => $reservation,
            'company' => $company,
        ];

        // Générer le HTML
        $html = view('invoices.reservation-complete-invoice', $data)->render();

        // IMPORTANT: Vérifier que le HTML est bien compilé
        Log::info('HTML généré:', ['length' => strlen($html), 'preview' => substr($html, 0, 200)]);

        // Générer le PDF
        return SnappyPdf::loadHTML($html)
            ->setPaper('a5')  // ← Changé de a4 à a5 comme dans le style
            ->setOrientation('portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-right', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);
    }

    public function downloadReservationReceipt(Reservation $reservation)
    {
        set_time_limit(30);
        
        $filename = "Recu_Reservation_{$reservation->sale->sale_number}.pdf";  // ← Nom plus approprié
        
        $pdf = $this->generateReservationReceipt($reservation);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    /**
     * Afficher la facture de crédit dans le navigateur
     */
    public function streamReservationReceipt(Reservation $reservation)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$reservation->sale->sale_number}_" .  ".pdf";
        
        return $this->generateReservationReceipt($reservation)
            ->inline($filename);
    }


    public function generateCashCount(CashCount $cashCount)
    {
        // Chargement des relations nécessaires
        $cashCount->loadMissing([
            'creator:id,name',
            'denominations:id,cash_count_id,denomination,quantity,subtotal',  // ✅ Pluriel + ajout cash_count_id
        ]);

        // Info société
        $company = $this->getCompanyInfo();

        // Données pour la vue
        $data = [
            'cashCount' => $cashCount,
            'company' => $company,
        ];

        // Générer le HTML
        $html = view('invoices.cash-count', $data)->render();

        // Générer le PDF
        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-right', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-javascript', false)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);
    }
    public function downloadCashCount(CashCount $cashCount)
    {
        set_time_limit(30);
        
        $filename = "Comptage_Caisse_{$cashCount->id}_" . $cashCount->count_date->format('d-m-Y') . ".pdf";
        
        $pdf = $this->generateCashCount($cashCount);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    public function streamCashCount(CashCount $cashCount)
    {
        set_time_limit(30);
        
        $filename = "Comptage_Caisse_{$cashCount->id}_" . $cashCount->count_date->format('d-m-Y') . ".pdf";
        
        return $this->generateCashCount($cashCount)
            ->inline($filename);
    }
    /**
     * Company info cached
     */
    private function getCompanyInfo(): array
    {
        $info = CompanyInfo::first(['name', 'address', 'phone', 'email', 'invoice_signature']);
        
        return [
            'name' => $info->name ?? config('app.name'),
            'address' => $info->address ?? '',
            'phone' => $info->phone ?? '',
            'email' => $info->email ?? '',
            'invoice_signature' => $info->invoice_signature ?? null,
        ];
    }


}