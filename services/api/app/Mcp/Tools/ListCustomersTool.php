<?php

namespace App\Mcp\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Recherche des clients (nom, téléphone) et retourne leur fiabilité, points de fidélité et statut.')]
class ListCustomersTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'query' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $limit = $input['limit'] ?? 10;

        $query = Customer::query();

        $queryText = trim((string) ($input['query'] ?? ''));
        if ($queryText !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$queryText}%")
                ->orWhere('phone', 'like', "%{$queryText}%"));
        }
        if (isset($input['is_active'])) {
            $query->where('is_active', (bool) $input['is_active']);
        }

        $customers = $query->orderByDesc('id')->limit($limit)->get();

        $data = $customers->map(fn ($customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'customer_number' => $customer->customer_number,
            'reliability_score' => (float) $customer->reliability_score,
            'loyalty_points' => (int) $customer->loyalty_points,
            'credit_limit' => (float) $customer->credit_limit,
            'is_active' => (bool) $customer->is_active,
        ])->toArray();

        return Response::json([
            'total' => count($data),
            'customers' => $data,
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
            'query' => $schema->string()
                ->description('Terme de recherche sur le nom ou le téléphone du client.')
                ->max(120),
            'is_active' => $schema->boolean()
                ->description('Filtrer les clients actifs uniquement.'),
            'limit' => $schema->integer()
                ->description('Nombre maximal de clients à retourner.')
                ->default(10)
                ->min(1)
                ->max(50),
        ];
    }
}
