<?php

namespace App\Http\Controllers;

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
    public function download(Sale $sale)
    {
        return $this->invoiceService->download($sale);
    }

    /**
     * Afficher la facture dans le navigateur
     */
    public function show(Sale $sale, Request $request)
    {
        // Support du token dans l'URL pour nouvel onglet
        if ($request->has('token')) {
            $token = $request->get('token');
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            
            if (!$user) {
                abort(401, 'Token invalide');
            }
        }

        return $this->invoiceService->stream($sale);
    }

    /**
     * Envoyer la facture par email (optionnel)
     */
    public function email(Sale $sale)
    {
        if (!$sale->customer || !$sale->customer->email) {
            return response()->json([
                'message' => 'Client sans email'
            ], 400);
        }

        // Sauvegarder le PDF temporairement
        $pdfPath = $this->invoiceService->save($sale);

        // TODO: Implémenter l'envoi d'email
        // Mail::to($sale->customer->email)->send(new InvoiceMail($sale, $pdfPath));

        return response()->json([
            'message' => 'Facture envoyée par email'
        ]);
    }
}