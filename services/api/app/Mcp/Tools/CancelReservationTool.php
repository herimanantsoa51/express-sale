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

#[Description('Annule une réservation active : marque la réservation comme annulée avec un motif obligatoire. Politique métier : AUCUN remboursement automatique de l\'acompte ni libération du stock réservé — régularisations manuelles par l\'opérateur (cancel-transaction-tool, stock-adjustment-tool).')]
class CancelReservationTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReservationService $reservationService): Response
    {
        $input = $request->validate([
            'reservation_id' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ], [
            'reservation_id.required' => "L'identifiant de la réservation est requis (reservation_id).",
            'reason.required' => 'Le motif d\'annulation est requis (reason).',
        ]);

        $reservation = Reservation::find($input['reservation_id']);
        if (! $reservation) {
            return Response::error("Aucune réservation ne correspond à l'identifiant {$input['reservation_id']}.");
        }
        if ($reservation->status === 'cancelled') {
            return Response::error('Cette réservation est déjà annulée.');
        }
        if ($reservation->status === 'completed') {
            return Response::error('Cette réservation est déjà finalisée, elle ne peut plus être annulée.');
        }

        try {
            $reservation = $reservationService->cancelReservation((int) $input['reservation_id'], $input['reason']);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::RESERVATION_CANCELLED,
                ' l\'annulation d\'une réservation via MCP a échoué',
                ['error' => $e->getMessage(), 'reservation_id' => $input['reservation_id']]
            );

            return Response::error('Échec de l\'annulation de la réservation : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::RESERVATION_CANCELLED,
            " a annulé la réservation {$reservation->reservation_number} via MCP",
            [
                'model_type' => Reservation::class,
                'model_id' => $reservation->id,
                'metadata' => $reservation->toArray(),
            ],
            "ventes/reservations/{$reservation->id}"
        );

        return Response::json([
            'message' => 'Réservation annulée. Régularisations manuelles requises : remboursement de l\'acompte (cancel-transaction-tool) et libération du stock (stock-adjustment-tool) à faire par l\'opérateur.',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'status' => $reservation->status,
                'cancellation_reason' => $reservation->cancellation_reason,
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
                ->description('Identifiant de la réservation à annuler.')
                ->required()
                ->min(1),
            'reason' => $schema->string()
                ->description("Motif de l'annulation (obligatoire, ex. 'client a changé d'avis').")
                ->required()
                ->max(500),
        ];
    }
}
