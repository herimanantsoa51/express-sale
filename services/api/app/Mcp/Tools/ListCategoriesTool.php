<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CategoryController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Liste les catégories de produits (arborescence racines/enfants). À consulter pour trouver la category_id avant de créer un produit, ou pour naviguer dans le catalogue.")]
class ListCategoriesTool extends Tool
{
    public function handle(Request $request, CategoryController $controller): Response
    {
        $input = $request->validate([
            'scope' => 'nullable|string|in:all,roots,children,for-sale',
            'parent_id' => 'nullable|integer|min:1',
        ]);

        $httpRequest = HttpRequest::create('/', 'GET', array_filter([
            'only_roots' => ($input['scope'] ?? '') === 'roots' ? 'true' : null,
            'parent_id' => $input['parent_id'] ?? null,
        ]));

        try {
            $response = match ($input['scope'] ?? 'all') {
                'roots' => $controller->index($httpRequest),
                'children' => $controller->index($httpRequest),
                'for-sale' => $controller->forSale($httpRequest),
                default => $controller->index($httpRequest),
            };
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json($response->getData(true));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->description("'all' (défaut), 'roots' (catégories racines), 'children' (enfants d'un parent, parent_id requis) ou 'for-sale' (catégories vendables).")
                ->enum(['all', 'roots', 'children', 'for-sale']),
            'parent_id' => $schema->integer()
                ->description("Identifiant du parent (requis pour scope='children')."),
        ];
    }
}
