<?php

namespace App\Mcp\Tools;

use App\Models\ExpenseCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les catégories de dépenses disponibles (loyer, salaires, transport...) avec leurs couleurs d\'affichage. À utiliser pour trouver l\'expense_category_id avant d\'enregistrer une dépense.')]
class ListExpenseCategoriesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = ExpenseCategory::query()
            ->select(['id', 'name', 'description', 'icon', 'is_active'])
            ->orderBy('name');

        if (! empty($input['search'])) {
            $query->where('name', 'ILIKE', "%{$input['search']}%");
        }

        $categories = $query->get();

        return Response::json([
            'total' => $categories->count(),
            'categories' => $categories->map(fn (ExpenseCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
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
            'search' => $schema->string()
                ->description('Recherche textuelle sur le nom de la catégorie.'),
        ];
    }
}
