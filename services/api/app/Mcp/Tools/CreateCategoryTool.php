<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée une catégorie de produits (racine ou sous-catégorie d\'une catégorie existante).')]
class CreateCategoryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'parent_id' => 'nullable|integer|min:1|exists:categories,id',
        ], [
            'name.required' => 'Le nom de la catégorie est requis (name).',
            'parent_id.exists' => "La catégorie parente n'existe pas — voir list-categories-tool.",
        ]);

        try {
            $category = Category::create([
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'parent_id' => $input['parent_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::CATEGORY_CREATED,
                ' la création de la catégorie via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::CATEGORY_CREATED,
            "a créé la catégorie {$category->name} via MCP",
            [
                'model_type' => Category::class,
                'model_id' => $category->id,
                'metadata' => $category->toArray(),
            ],
            "produits/categories"
        );

        return Response::json([
            'message' => 'Catégorie créée avec succès.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'description' => $category->description,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nom de la catégorie.')
                ->required()
                ->max(255),
            'parent_id' => $schema->integer()
                ->description("Identifiant de la catégorie parente (sous-catégorie si fourni, racine sinon)."),
            'description' => $schema->string()
                ->description('Description de la catégorie.')
                ->max(2000),
        ];
    }
}
