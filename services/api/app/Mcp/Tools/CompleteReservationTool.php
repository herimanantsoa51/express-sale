<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Finalise une réservation active : encaisse le montant restant dû sur un compte de trésorerie, transforme le stock réservé en stock vendu et marque la réservation comme complétée. La réservation doit être active (pending, confirmed ou partial_paid).')]
class CompleteReservationTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReservationService $reservationService): Response
    {
        $input = $request->validate([
            'reservation_id' => 'required|integer|min:1',
            'account_id' => 'required|integer|min:1|exists:accounts,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'reservation_id.required' => "L'identifiant de la réservation est requis (reservation_id).",
            'account_id.required' => "Le compte de trésorerie est requis (account_id) pour encaisser le paiement final.",
            'account_id.exists' => "Le compte sélectionné n'existe pas.",
        ]);

        $reservation = Reservation::find($input['reservation_id']);
        if (! $reservation) {
            return Response::error("Aucune réservation ne correspond à l'identifiant {$input['reservation_id']}.");
        }
        if (in_array($reservation->status, ['completed', 'cancelled', 'expired'])) {
            return Response::error("Cette réservation est déjà {$reservation->status}, elle ne peut plus être finalisée.");
        }

        try {
            $reservation = $reservationService->completeReservation((int) $input['reservation_id'], [
                'account_id' => (int) $input['account_id'],
                'notes' => $input['notes'] ?? '',
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::RESERVATION_COMPLETED,
                ' la finalisation d\'une réservation via MCP a échoué',
                ['error' => $e->getMessage(), 'reservation_id' => $input['reservation_id']]
            );

            return Response::error('Échec de la finalisation de la réservation : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::RESERVATION_COMPLETED,
            " a finalisé la réservation {$reservation->reservation_number} via MCP",
            [
                'model_type' => Reservation::class,
                'model_id' => $reservation->id,
                'metadata' => $reservation->toArray(),
            ],
            "ventes/reservations/{$reservation->id}"
        );

        return Response::json([
            'message' => 'Réservation finalisée avec succès.',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'status' => $reservation->status,
                'total_amount' => (float) $reservation->total_amount,
                'deposit_amount' => (float) $reservation->deposit_amount,
                'remaining_amount' => (float) $reservation->remaining_amount,
                'completed_at' => $reservation->completed_at?->toISOString(),
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
            'reservation_id' => $schema->integer()
                ->description("Identifiant de la réservation à finaliser.")
                ->required()
                ->min(1),
            'account_id' => $schema->integer()
                ->description("Identifiant du compte de trésorerie où encaisser le paiement final (le montant restant dû est encaissé en totalité).")
                ->required()
                ->min(1),
            'notes' => $schema->string()
                ->description('Notes sur le paiement final.')
                ->max(500),
        ];
    }
}
