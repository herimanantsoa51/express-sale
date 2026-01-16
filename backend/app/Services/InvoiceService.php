<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generate(Sale $sale)
    {
        // Chargement des relations
        $sale->load([
            'customer',
            'user',
            'items.variant.product',
            'accountTransaction'
        ]);
        return Pdf::loadView('invoices.template', [
            'sale' => $sale,
            'company' => $this->getCompanyInfo(),
        ])
        ->setPaper('a4', 'portrait');
    }

    public function download(Sale $sale)
    {
        return $this->generate($sale)
            ->download("facture-{$sale->sale_number}.pdf");
    }

    public function stream(Sale $sale)
    {
        return $this->generate($sale)
            ->stream("facture-{$sale->sale_number}.pdf");
    }

    public function save(Sale $sale): string
    {
        $pdf = $this->generate($sale);
        $path = "invoices/{$sale->sale_number}.pdf";
        
        Storage::disk('local')->put($path, $pdf->output());
        
        return $path;
    }

    private function getCompanyInfo(): array
    {
        return [
            'name' => config('app.company_name', 'Express Sale'),
            'address' => config('app.company_address', ''),
            'phone' => config('app.company_phone', ''),
            'email' => config('app.company_email', ''),
            'logo' => public_path('images/logo.png'),
        ];
    }
}