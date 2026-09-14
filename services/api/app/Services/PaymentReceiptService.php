<?php

namespace App\Services;

use App\Models\CompanyInfo;
use App\Models\InstallmentTransaction;
use Barryvdh\Snappy\Facades\SnappyPdf;

class PaymentReceiptService
{
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
        $html = view('invoices.payment-receipt', $data)->render();

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
        $filename = "Recu_Paiement_{$credit->sale->sale_number}_".$installmentTransaction->payment_date->format('d-m-Y').'.pdf';

        $pdf = $this->generatePaymentReceipt($installmentTransaction);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Afficher le reçu de paiement dans le navigateur
     */
    public function streamPaymentReceipt(InstallmentTransaction $installmentTransaction)
    {
        set_time_limit(30);

        $credit = $installmentTransaction->installment->credit;
        $filename = "Recu_Paiement_{$credit->sale->sale_number}_".$installmentTransaction->payment_date->format('d-m-Y').'.pdf';

        return $this->generatePaymentReceipt($installmentTransaction)
            ->inline($filename);
    }

    /**
     * Company info
     */
    private function getCompanyInfo(): array
    {
        $info = CompanyInfo::first(['name', 'address', 'phone', 'email']);

        return [
            'name' => $info->name ?? config('app.name'),
            'address' => $info->address ?? '',
            'phone' => $info->phone ?? '',
            'email' => $info->email ?? '',
        ];
    }
}
