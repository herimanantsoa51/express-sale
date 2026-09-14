<?php

namespace App\Mcp\Tools;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Sale;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Historique complet d\'un client : fiche (contact, plafond de crédit, points de fidélité, fiabilité), créduits en cours (montant dû), et dernières ventes, crédits et réservations. À consulter avant une vente à crédit ou une réservation.')]
class ListCustomerHistoryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'customer_id' => 'required|integer|min:1',
        ], [
            'customer_id.required' => "L'identifiant du client est requis (customer_id) — voir list-customers-tool.",
        ]);

        $customer = Customer::find($input['customer_id']);
        if (! $customer) {
            return Response::error("Aucun client ne correspond à l'identifiant {$input['customer_id']}.");
        }

        // Crédits en cours (une requête agrégée)
        $creditsOpen = Credit::where('customer_id', $customer->id)
            ->whereIn('status', ['active', 'partial_paid', 'overdue'])
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(amount_due), 0) AS due')
            ->first();

        $recentSales = Sale::where('customer_id', $customer->id)
            ->select(['id', 'sale_number', 'sale_date', 'total_amount', 'payment_status', 'status'])
            ->orderByDesc('sale_date')
            ->limit(5)
            ->get();

        $recentCredits = Credit::where('customer_id', $customer->id)
            ->select(['id', 'credit_number', 'credit_date', 'due_date', 'total_amount', 'amount_paid', 'amount_due', 'status'])
            ->orderByDesc('credit_date')
            ->limit(5)
            ->get();

        $recentReservations = Reservation::where('customer_id', $customer->id)
            ->select(['id', 'reservation_number', 'reservation_date', 'expiry_date', 'total_amount', 'deposit_amount', 'remaining_amount', 'status'])
            ->orderByDesc('reservation_date')
            ->limit(5)
            ->get();

        return Response::json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'customer_number' => $customer->customer_number,
                'reliability_score' => (float) $customer->reliability_score,
                'loyalty_points' => (int) $customer->loyalty_points,
                'credit_limit' => (float) $customer->credit_limit,
                'is_active' => $customer->is_active,
                'has_overdue_credits' => $customer->hasOverdueCredits(),
            ],
            'open_credits' => [
                'count' => (int) $creditsOpen->c,
                'total_amount_due' => (float) $creditsOpen->due,
                'credit_limit_remaining' => max(0, (float) $customer->credit_limit - (float) $creditsOpen->due),
            ],
            'recent_sales' => $recentSales->map(fn (Sale $s) => [
                'id' => $s->id,
                'sale_number' => $s->sale_number,
                'sale_date' => $s->sale_date?->toDateString(),
                'total_amount' => (float) $s->total_amount,
                'payment_status' => $s->payment_status?->value,
                'status' => $s->status?->value,
            ])->toArray(),
            'recent_credits' => $recentCredits->map(fn (Credit $c) => [
                'id' => $c->id,
                'credit_number' => $c->credit_number,
                'credit_date' => $c->credit_date?->toDateString(),
                'due_date' => $c->due_date?->toDateString(),
                'total_amount' => (float) $c->total_amount,
                'amount_paid' => (float) $c->amount_paid,
                'amount_due' => (float) $c->amount_due,
                'status' => $c->status,
            ])->toArray(),
            'recent_reservations' => $recentReservations->map(fn (Reservation $r) => [
                'id' => $r->id,
                'reservation_number' => $r->reservation_number,
                'reservation_date' => $r->reservation_date?->toDateString(),
                'expiry_date' => $r->expiry_date?->toDateString(),
                'total_amount' => (float) $r->total_amount,
                'deposit_amount' => (float) $r->deposit_amount,
                'remaining_amount' => (float) $r->remaining_amount,
                'status' => $r->status,
            ])->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()
                ->description('Identifiant du client.')
                ->required()
                ->min(1),
        ];
    }
}
