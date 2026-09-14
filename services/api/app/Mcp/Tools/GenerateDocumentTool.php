<?php

namespace App\Mcp\Tools;

use App\Models\CashCount;
use App\Models\Credit;
use App\Models\InstallmentTransaction;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\InvoiceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Génère un document (facture ou reçu) : type=sale (facture de vente immédiate), credit (facture de vente à crédit), installment (reçu de paiement d\'échéance), reservation (devis de réservation), reservation-receipt (reçu d\'acompte), cash-count (rapport de comptage de caisse). Retourne le PDF en base64 (download=false) utilisable directement comme pièce jointe d\'email, ou le stocke et retourne son chemin (download=true).')]
class GenerateDocumentTool extends Tool
{
    private const DOCUMENTS = [
        'sale' => 'sale',
        'credit' => 'credit',
        'installment' => 'installment',
        'reservation' => 'reservation',
        'reservation-receipt' => 'reservation-receipt',
        'cash-count' => 'cash-count',
    ];

    /**
     * Les générateurs PDF du service de facturation sont réutilisés
     * tels quels : mêmes documents que le frontend.
     */
    public function handle(Request $request, InvoiceService $invoiceService): Response
    {
        $input = $request->validate([
            'type' => 'required|string|in:sale,credit,installment,reservation,reservation-receipt,cash-count',
            'reference_id' => 'required|integer|min:1',
            'download' => 'nullable|boolean',
        ], [
            'type.required' => "Le type de document est requis (type) : sale, credit, installment, reservation, reservation-receipt ou cash-count.",
            'reference_id.required' => "L'identifiant de référence est requis (reference_id) : id de la vente, du crédit, de la transaction d'échéance, de la réservation ou du comptage.",
        ]);

        try {
            [$pdf, $filename] = $this->generate($input['type'], (int) $input['reference_id'], $invoiceService);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        } catch (\Throwable $e) {
            return Response::error('Échec de la génération du document : '.$e->getMessage());
        }

        if ($input['download'] ?? false) {
            $relativePath = 'documents/'.date('Y/m').'/'.$filename;
            Storage::disk('public')->put($relativePath, $pdf->output());

            return Response::json([
                'message' => 'Document généré et stocké avec succès.',
                'document' => [
                    'type' => $input['type'],
                    'reference_id' => (int) $input['reference_id'],
                    'filename' => $filename,
                    'path' => $relativePath,
                    'size_bytes' => strlen($pdf->output()),
                ],
            ]);
        }

        return Response::json([
            'message' => 'Document généré avec succès. Le contenu base64 peut être attaché directement à un email via send-document-email-tool (document_type + reference_id).',
            'document' => [
                'type' => $input['type'],
                'reference_id' => (int) $input['reference_id'],
                'filename' => $filename,
                'pdf_base64' => base64_encode($pdf->output()),
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($pdf->output()),
            ],
        ]);
    }

    /**
     * @return array{0: object, 1: string}
     */
    public function generate(string $type, int $referenceId, InvoiceService $invoiceService): array
    {
        return match ($type) {
            'sale' => [$invoiceService->generateSale(Sale::findOrFail($referenceId)), 'Facture_VNT-'.$referenceId.'.pdf'],
            'credit' => [$invoiceService->generateCredit(Credit::findOrFail($referenceId)), 'Facture_CRD-'.$referenceId.'.pdf'],
            'installment' => [$invoiceService->generatePaymentReceipt(InstallmentTransaction::findOrFail($referenceId)), 'Recu_echeance-'.$referenceId.'.pdf'],
            'reservation' => [$invoiceService->generateReservationInvoice(Reservation::findOrFail($referenceId)), 'Devis_RSV-'.$referenceId.'.pdf'],
            'reservation-receipt' => [$invoiceService->generateReservationReceipt(Reservation::findOrFail($referenceId)), 'Recu_acompte_RSV-'.$referenceId.'.pdf'],
            'cash-count' => [$invoiceService->generateCashCount(CashCount::findOrFail($referenceId)), 'Rapport_caisse-'.$referenceId.'.pdf'],
            default => throw new \InvalidArgumentException('Type de document inconnu.'),
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description("Type de document : 'sale', 'credit', 'installment', 'reservation', 'reservation-receipt' ou 'cash-count'.")
                ->required()
                ->enum(['sale', 'credit', 'installment', 'reservation', 'reservation-receipt', 'cash-count']),
            'reference_id' => $schema->integer()
                ->description("Identifiant de la vente, du crédit, de la transaction d'échéance, de la réservation ou du comptage.")
                ->required()
                ->min(1),
            'download' => $schema->boolean()
                ->description("true pour stocker le PDF et obtenir son chemin, false (défaut) pour obtenir le contenu base64."),
        ];
    }
}
