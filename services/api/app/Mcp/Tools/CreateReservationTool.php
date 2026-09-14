<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée une réservation pour un client existant : stock FIFO réservé (bloqué jusqu’à finalisation ou expiration), acompte encaissé sur un compte de trésorerie si versé, transaction comptable automatique. Aucun encaissement sans acompte.')]
class CreateReservationTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReservationService $reservationService): Response
    {
        $input = $request->validate([
            'customer_id' => 'required|integer|min:1|exists:customers,id',
            'expiry_date' => 'required|date|after:today',
            'deposit_amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|integer|min:1|exists:accounts,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|min:1',
            'items.*.location_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,mixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'customer_id.required' => 'Le client est requis pour une réservation (customer_id).',
            'customer_id.exists' => "Le client sélectionné n'existe pas.",
            'expiry_date.required' => "La date d'expiration est requise (expiry_date, format Y-m-d).",
            'expiry_date.after' => "La date d'expiration doit être dans le futur.",
            'deposit_amount.required' => "Le montant de l'acompte est requis (deposit_amount, 0 si aucun).",
            'deposit_amount.min' => "L'acompte ne peut pas être négatif.",
            'account_id.exists' => "Le compte sélectionné n'existe pas.",
            'items.required' => 'Au moins un article est requis (items).',
            'items.min' => 'Au moins un article est requis.',
        ]);
        $customer = Customer::find($input['customer_id']);
        if (! $customer) {
            return Response::error("Aucun client ne correspond à l'identifiant fourni.");
        }

        // Garde-fou : acompte obligatoire si montant > 0, total estimé sinon
        $depositAmount = (float) ($input['deposit_amount'] ?? 0);
        if ($depositAmount > 0 && empty($input['account_id'])) {
            return Response::error("Le compte de trésorerie (account_id) est requis pour encaisser l'acompte.");
        }

        try {
            $reservation = $reservationService->createReservation([
                'customer_id' => (int) $input['customer_id'],
                'expiry_date' => $input['expiry_date'],
                'deposit_amount' => $depositAmount,
                'account_id' => isset($input['account_id']) ? (int) $input['account_id'] : null,
                'payment_method' => $input['payment_method'] ?? null,
                'discount_amount' => $input['discount_amount'] ?? null,
                'discount_reason' => $input['discount_reason'] ?? null,
                'notes' => $input['notes'] ?? null,
                'items' => array_map(fn (array $item) => [
                    'variant_id' => (int) $item['variant_id'],
                    'location_id' => (int) $item['location_id'],
                    'quantity' => (int) $item['quantity'],
                ], $input['items']),
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::RESERVATION_CREATED,
                ' la création de la réservation via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création de la réservation : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::RESERVATION_CREATED,
            " a créé une réservation d'une valeur de {$reservation->total_amount} via MCP",
            [
                'model_type' => Reservation::class,
                'model_id' => $reservation->id,
                'metadata' => $reservation->toArray(),
            ],
            "ventes/reservations/{$reservation->id}"
        );

        return Response::json([
            'message' => 'Réservation créée avec succès.',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'customer_id' => $reservation->customer_id,
                'subtotal' => (float) $reservation->subtotal,
                'discount_amount' => (float) $reservation->discount_amount,
                'total_amount' => (float) $reservation->total_amount,
                'deposit_amount' => (float) $reservation->deposit_amount,
                'remaining_amount' => (float) $reservation->remaining_amount,
                'status' => $reservation->status,
                'expiry_date' => $reservation->expiry_date?->toDateString(),
                'items' => collect($reservation->items)->map(fn ($item) => [
                    'variant_id' => (int) $item->variant_id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ])->toArray(),
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()
                ->description('Identifiant du client (obligatoire pour une réservation).')
                ->required()
                ->min(1),
            'expiry_date' => $schema->string()
                ->description("Date d'expiration de la réservation au format Y-m-d (doit être dans le futur).")
                ->required(),
            'deposit_amount' => $schema->number()
                ->description("Montant de l'acompte versé en Ariary (0 si aucun acompte)."),
            'account_id' => $schema->integer()
                ->description("Identifiant du compte de trésorerie où encaisser l'acompte (requis si acompte > 0).")
                ->min(1),
            'items' => $schema->array()
                ->description('Articles à réserver.')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'variant_id' => $schema->integer()
                            ->description('Identifiant de la variante de produit.')
                            ->required()
                            ->min(1),
                        'location_id' => $schema->integer()
                            ->description("Identifiant de l'emplacement de stock où la variante est disponible.")
                            ->required()
                            ->min(1),
                        'quantity' => $schema->integer()
                            ->description('Quantité à réserver.')
                            ->required()
                            ->min(1),
                    ])
                ),
            'payment_method' => $schema->string()
                ->description("Méthode de paiement de l'acompte : cash, mobile_money, bank_transfer ou mixed.")
                ->enum(['cash', 'mobile_money', 'bank_transfer', 'mixed']),
            'discount_amount' => $schema->number()
                ->description('Montant de la remise globale en Ariary.')
                ->min(0),
            'discount_reason' => $schema->string()
                ->description('Motif de la remise.')
                ->max(255),
            'notes' => $schema->string()
                ->description('Notes sur la réservation.')
                ->max(1000),
        ];
    }
}
