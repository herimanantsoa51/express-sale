<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CompanyInfo;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\Cache;

class CreditInvoiceService
{
    /**
     * Génère le PDF de la facture de crédit
     */
    public function generateCreditInvoice(Credit $credit)
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
    public function downloadCreditInvoice(Credit $credit)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$credit->sale->sale_number}_" . $credit->credit_date->format('d-m-Y') . ".pdf";
        
        $pdf = $this->generateCreditInvoice($credit);
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Afficher la facture de crédit dans le navigateur
     */
    public function streamCreditInvoice(Credit $credit)
    {
        set_time_limit(30);
        
        $filename = "Facture_Credit_{$credit->sale->sale_number}_" . $credit->credit_date->format('d-m-Y') . ".pdf";
        
        return $this->generateCreditInvoice($credit)
            ->inline($filename);
    }

    /**
     * Company info
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