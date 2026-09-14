<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Services\CreditSaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée une vente à crédit pour un client existant : échéances générées automatiquement (nombre + fréquence), stock FIFO débité, contrôle de la limite de crédit. Aucun encaissement immédiat — seul le paiement des échéances créditera un compte de trésorerie.')]
class CreateCreditSaleTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, CreditSaleService $creditSaleService): Response
    {
        $input = $request->validate([
            'customer_id' => 'required|integer|min:1|exists:customers,id',
            'due_date' => 'required|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|min:1',
            'items.*.location_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'installment_count' => 'nullable|integer|min:1|max:36',
            'installment_frequency' => 'nullable|string|in:weekly,biweekly,monthly',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,mixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'customer_id.required' => 'Le client est requis pour une vente à crédit (customer_id).',
            'customer_id.exists' => "Le client sélectionné n'existe pas.",
            'due_date.required' => "La date d'échéance est requise (due_date, format Y-m-d).",
            'due_date.after_or_equal' => "La date d'échéance doit être aujourd'hui ou dans le futur.",
            'items.required' => 'Au moins un article est requis (items).',
            'items.min' => 'Au moins un article est requis.',
            'items.*.variant_id.required' => 'Chaque article doit préciser la variante de produit (variant_id).',
            'items.*.location_id.required' => 'Chaque article doit préciser son emplacement de stock (location_id).',
            'items.*.quantity.required' => 'Chaque article doit préciser une quantité (quantity).',
            'items.*.quantity.min' => 'La quantité doit être au moins 1.',
            'installment_count.min' => "Le nombre d'échéances doit être au moins 1.",
            'installment_count.max' => "Le nombre d'échéances ne peut pas dépasser 36.",
            'installment_frequency.in' => 'La fréquence doit être : weekly, biweekly ou monthly.',
            'payment_method.in' => 'La méthode de paiement doit être : cash, mobile_money, bank_transfer ou mixed.',
        ]);

        $customer = Customer::find($input['customer_id']);
        if (! $customer) {
            return Response::error("Aucun client ne correspond à l'identifiant fourni.");
        }

        // Garde-fou : total estimé (prix catalogue − remise) vs crédit disponible
        $estimatedTotal = collect($input['items'])->sum(function (array $item) {
            $variant = ProductVariant::find($item['variant_id']);

            return $variant ? ((float) $variant->getFinalPriceAttribute()) * (int) $item['quantity'] : 0;
        }) - (float) ($input['discount_amount'] ?? 0);

        $availableCredit = (float) $customer->getAvailableCredit();
        if ($estimatedTotal > $availableCredit) {
            return Response::error(
                "Crédit insuffisant pour {$customer->name} : total estimé {$estimatedTotal} Ar, "
                ."crédit disponible {$availableCredit} Ar (limite {$customer->credit_limit} Ar, "
                .'utilisé '.$customer->getCurrentCreditUsage().' Ar).'
            );
        }
        try {
            $credit = $creditSaleService->createCreditSale([
                'customer_id' => (int) $input['customer_id'],
                'due_date' => $input['due_date'],
                'installment_count' => $input['installment_count'] ?? 1,
                'installment_frequency' => $input['installment_frequency'] ?? 'monthly',
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
                ActivityAction::CREDIT_CREATED,
                ' la création de la vente à crédit via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création de la vente à crédit : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::CREDIT_CREATED,
            " a créé une vente à crédit d'une valeur de {$credit->total_amount} via MCP",
            [
                'model_type' => Credit::class,
                'model_id' => $credit->id,
                'metadata' => $credit->toArray(),
            ],
            "ventes/credits/{$credit->id}"
        );

        return Response::json([
            'message' => 'Vente à crédit créée avec succès.',
            'credit' => [
                'id' => $credit->id,
                'credit_number' => $credit->credit_number,
                'customer_id' => $credit->customer_id,
                'subtotal' => (float) $credit->subtotal,
                'discount_amount' => (float) $credit->discount_amount,
                'total_amount' => (float) $credit->total_amount,
                'amount_paid' => (float) $credit->amount_paid,
                'amount_due' => (float) $credit->amount_due,
                'status' => $credit->status,
                'due_date' => $credit->due_date?->toDateString(),
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
                ->description('Identifiant du client (obligatoire pour une vente à crédit).')
                ->required()
                ->min(1),
            'due_date' => $schema->string()
                ->description("Date d'échéance globale au format Y-m-d (aujourd'hui ou futur).")
                ->required(),
            'items' => $schema->array()
                ->description('Articles à vendre à crédit.')
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
                            ->description('Quantité à vendre.')
                            ->required()
                            ->min(1),
                    ])
                ),
            'installment_count' => $schema->integer()
                ->description("Nombre d'échéances générées automatiquement (1 par défaut, 36 maximum).")
                ->min(1),
            'installment_frequency' => $schema->string()
                ->description('Fréquence des échéances : weekly, biweekly ou monthly (monthly par défaut).')
                ->enum(['weekly', 'biweekly', 'monthly']),
            'payment_method' => $schema->string()
                ->description('Méthode de paiement prévue : cash, mobile_money, bank_transfer ou mixed.')
                ->enum(['cash', 'mobile_money', 'bank_transfer', 'mixed']),
            'discount_amount' => $schema->number()
                ->description('Montant de la remise globale en Ariary.')
                ->min(0),
            'discount_reason' => $schema->string()
                ->description('Motif de la remise.')
                ->max(255),
            'notes' => $schema->string()
                ->description('Notes sur la vente à crédit.')
                ->max(1000),
        ];
    }
}
