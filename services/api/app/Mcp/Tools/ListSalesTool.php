<?php

namespace App\Mcp\Tools;

use App\Models\Credit;
use App\Models\Reservation;
use App\Models\Sale;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les ventes selon leur type : ventes immédiates (comptant), ventes à crédit (avec montant restant dû) ou réservations (avec acompte et restant à payer). Filtres disponibles : statut, client, recherche textuelle, période. Les résultats sont paginés.')]
class ListSalesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'type' => 'nullable|string|in:immediate,credit,reservation',
            'status' => 'nullable|string|max:30',
            'customer_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ], [
            'type.in' => 'Le type doit être : immediate, credit ou reservation.',
        ]);

        $type = $input['type'] ?? 'immediate';
        $perPage = min($input['limit'] ?? 20, 100);
        $page = $input['page'] ?? 1;

        return match ($type) {
            'credit' => $this->listCredits($input, $perPage, $page),
            'reservation' => $this->listReservations($input, $perPage, $page),
            default => $this->listImmediate($input, $perPage, $page),
        };
    }

    private function listImmediate(array $input, int $perPage, int $page): Response
    {
        $query = Sale::query()
            ->select(['id', 'sale_number', 'customer_id', 'sale_date', 'subtotal', 'discount_amount', 'total_amount', 'payment_status', 'payment_method', 'status'])
            ->with(['customer:id,name,customer_number'])
            ->orderBy('sale_date', 'desc');

        if (! empty($input['status'])) {
            $query->where('status', strtoupper($input['status']));
        }
        if (! empty($input['customer_id'])) {
            $query->where('customer_id', (int) $input['customer_id']);
        }
        if (! empty($input['search'])) {
            $query->where('sale_number', 'ILIKE', "%{$input['search']}%");
        }
        if (! empty($input['date_from'])) {
            $query->where('sale_date', '>=', $input['date_from']);
        }
        if (! empty($input['date_to'])) {
            $query->where('sale_date', '<=', $input['date_to'].' 23:59:59');
        }

        $total = (clone $query)->count();
        $sales = $query->forPage($page, $perPage)->get();

        return Response::json([
            'type' => 'immediate',
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
            'sales' => $sales->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer' => $sale->customer?->name,
                'sale_date' => $sale->sale_date?->toISOString(),
                'subtotal' => (float) $sale->subtotal,
                'discount_amount' => (float) $sale->discount_amount,
                'total_amount' => (float) $sale->total_amount,
                'payment_status' => $sale->payment_status?->value,
                'payment_method' => $sale->payment_method?->value,
                'status' => $sale->status?->value,
            ])->toArray(),
        ]);
    }

    private function listCredits(array $input, int $perPage, int $page): Response
    {
        $query = Credit::query()
            ->select(['id', 'credit_number', 'customer_id', 'credit_date', 'due_date', 'subtotal', 'discount_amount', 'total_amount', 'amount_paid', 'amount_due', 'status'])
            ->with(['customer:id,name,customer_number'])
            ->orderBy('credit_date', 'desc');

        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (! empty($input['customer_id'])) {
            $query->where('customer_id', (int) $input['customer_id']);
        }
        if (! empty($input['search'])) {
            $query->search($input['search']);
        }
        if (! empty($input['date_from'])) {
            $query->where('credit_date', '>=', $input['date_from']);
        }
        if (! empty($input['date_to'])) {
            $query->where('credit_date', '<=', $input['date_to'].' 23:59:59');
        }

        $total = (clone $query)->count();
        $credits = $query->forPage($page, $perPage)->get();

        return Response::json([
            'type' => 'credit',
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
            'credits' => $credits->map(fn (Credit $credit) => [
                'id' => $credit->id,
                'credit_number' => $credit->credit_number,
                'customer' => $credit->customer?->name,
                'credit_date' => $credit->credit_date?->toDateString(),
                'due_date' => $credit->due_date?->toDateString(),
                'total_amount' => (float) $credit->total_amount,
                'amount_paid' => (float) $credit->amount_paid,
                'amount_due' => (float) $credit->amount_due,
                'status' => $credit->status,
            ])->toArray(),
        ]);
    }

    private function listReservations(array $input, int $perPage, int $page): Response
    {
        $query = Reservation::query()
            ->select(['id', 'reservation_number', 'customer_id', 'reservation_date', 'expiry_date', 'subtotal', 'discount_amount', 'total_amount', 'deposit_amount', 'remaining_amount', 'status'])
            ->with(['customer:id,name,customer_number'])
            ->orderBy('reservation_date', 'desc');

        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (! empty($input['customer_id'])) {
            $query->where('customer_id', (int) $input['customer_id']);
        }
        if (! empty($input['search'])) {
            $query->search($input['search']);
        }
        if (! empty($input['date_from'])) {
            $query->where('reservation_date', '>=', $input['date_from']);
        }
        if (! empty($input['date_to'])) {
            $query->where('reservation_date', '<=', $input['date_to'].' 23:59:59');
        }

        $total = (clone $query)->count();
        $reservations = $query->forPage($page, $perPage)->get();

        return Response::json([
            'type' => 'reservation',
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
            'reservations' => $reservations->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'customer' => $reservation->customer?->name,
                'reservation_date' => $reservation->reservation_date?->toISOString(),
                'expiry_date' => $reservation->expiry_date?->toDateString(),
                'total_amount' => (float) $reservation->total_amount,
                'deposit_amount' => (float) $reservation->deposit_amount,
                'remaining_amount' => (float) $reservation->remaining_amount,
                'status' => $reservation->status,
            ])->toArray(),
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
            'type' => $schema->string()
                ->description("Type de vente à lister : 'immediate' (comptant, par défaut), 'credit' ou 'reservation'.")
                ->enum(['immediate', 'credit', 'reservation']),
            'status' => $schema->string()
                ->description("Filtrer par statut. Ventes immédiates : 'CONFIRMED', 'CANCELLED'. Crédits : 'active', 'partial_paid', 'paid', 'cancelled'. Réservations : 'pending', 'confirmed', 'partial_paid', 'completed', 'cancelled', 'expired'."),
            'customer_id' => $schema->integer()
                ->description('Filtrer par identifiant de client.'),
            'search' => $schema->string()
                ->description("Recherche par numéro (ex. 'VT-2026...', 'CR-2026...', 'RS-2026...') ou nom/numéro de client (crédits et réservations)."),
            'date_from' => $schema->string()
                ->description('Date de début de la période (format Y-m-d).'),
            'date_to' => $schema->string()
                ->description('Date de fin de la période (format Y-m-d).'),
            'limit' => $schema->integer()
                ->description('Nombre de résultats par page (1 à 100, 20 par défaut).')
                ->min(1)
                ->max(100),
            'page' => $schema->integer()
                ->description('Numéro de page (1 par défaut).')
                ->min(1),
        ];
    }
}
