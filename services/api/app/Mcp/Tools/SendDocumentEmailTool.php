<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Services\InvoiceService;
use App\Services\MailService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Envoie un document (facture de vente, facture de crédit, reçu d\'échéance, devis ou reçu d\'acompte, rapport de caisse) par email à une adresse : le PDF est généré et joint automatiquement. Configurer les variables MAIL_* dans .env avec un vrai serveur SMTP — sinon l\'email est seulement journalisé (MAIL_MAILER=log).')]
class SendDocumentEmailTool extends Tool
{
    /**
     * Le générateur de documents et le service d'envoi sont réutilisés :
     * mêmes PDF que le frontend, envoi SMTP standard de Laravel.
     */
    public function handle(
        Request $request,
        InvoiceService $invoiceService,
        MailService $mailService,
        GenerateDocumentTool $documentTool,
    ): Response {
        $input = $request->validate([
            'type' => 'required|string|in:sale,credit,installment,reservation,reservation-receipt,cash-count',
            'reference_id' => 'required|integer|min:1',
            'to' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:5000',
        ], [
            'type.required' => "Le type de document est requis (type) : sale, credit, installment, reservation, reservation-receipt ou cash-count.",
            'reference_id.required' => "L'identifiant de référence est requis (reference_id).",
            'to.required' => "L'adresse email du destinataire est requise (to).",
            'to.email' => "L'adresse email du destinataire est invalide.",
        ]);

        try {
            [$pdf, $filename] = $documentTool->generate($input['type'], (int) $input['reference_id'], $invoiceService);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        } catch (\Throwable $e) {
            return Response::error('Échec de la génération du document : '.$e->getMessage());
        }

        try {
            $mailService->sendDocument(
                $input['to'],
                $input['subject'] ?? $this->defaultSubject($input['type'], $filename),
                $input['body'] ?? $this->defaultBody($input['type'], $filename),
                $pdf->output(),
                $filename,
            );
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::FILE_UPLOADED,
                " l'envoi du document {$filename} par email via MCP a échoué",
                ['error' => $e->getMessage(), 'to' => $input['to']]
            );

            return Response::error("Échec de l'envoi de l'email : ".$e->getMessage());
        }

        return Response::json([
            'message' => config('mail.default') === 'log'
                ? "Email enregistré dans les logs (mode MAIL_MAILER=log — configurer un vrai SMTP dans .env pour un envoi réel)."
                : 'Email envoyé avec succès.',
            'email' => [
                'to' => $input['to'],
                'subject' => $input['subject'] ?? $this->defaultSubject($input['type'], $filename),
                'attachment' => $filename,
            ],
        ]);
    }

    private function defaultSubject(string $type, string $filename): string
    {
        $label = match ($type) {
            'sale' => 'Votre facture',
            'credit' => 'Votre facture (vente à crédit)',
            'installment' => 'Votre reçu de paiement',
            'reservation' => 'Votre devis de réservation',
            'reservation-receipt' => "Votre reçu d'acompte",
            'cash-count' => 'Rapport de comptage de caisse',
            default => 'Votre document',
        };

        return config('app.name')." — {$label}";
    }

    private function defaultBody(string $type, string $filename): string
    {
        return match ($type) {
            'sale' => "Bonjour,\n\nVeuillez trouver ci-joint votre facture ({$filename}).\n\nMerci de votre confiance.",
            'credit' => "Bonjour,\n\nVeuillez trouver ci-joint votre facture de vente à crédit ({$filename}).",
            'installment' => "Bonjour,\n\nVeuillez trouver ci-joint votre reçu de paiement ({$filename}).",
            'reservation' => "Bonjour,\n\nVeuillez trouver ci-joint votre devis de réservation ({$filename}).",
            'reservation-receipt' => "Bonjour,\n\nVeuillez trouver ci-joint votre reçu d'acompte ({$filename}).",
            'cash-count' => "Bonjour,\n\nVeuillez trouver ci-joint le rapport de comptage de caisse ({$filename}).",
            default => "Bonjour,\n\nVeuillez trouver ci-joint votre document ({$filename}).",
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
            'to' => $schema->string()
                ->description("Adresse email du destinataire (ex. le client).")
                ->required()
                ->max(255),
            'subject' => $schema->string()
                ->description("Objet de l'email (généré par défaut si omis).")
                ->max(255),
            'body' => $schema->string()
                ->description("Corps de l'email (généré par défaut si omis).")
                ->max(5000),
        ];
    }
}
