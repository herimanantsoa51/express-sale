<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée un nouveau fournisseur (nom unique, contact, wechat, notes d\'accessibilité, score de fiabilité 0-10). Le fournisseur pourra ensuite recevoir des commandes via create-stock-receipt-tool.')]
class CreateSupplierTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'wechat' => 'nullable|string|max:100',
            'profile' => 'nullable|string|max:2000',
            'contact' => 'nullable|string|max:255',
            'accessibility_notes' => 'nullable|string|max:2000',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
        ], [
            'name.required' => 'Le nom du fournisseur est requis (name).',
            'name.unique' => "Un fournisseur portant ce nom existe déjà — utiliser list-suppliers-tool pour le retrouver.",
            'reliability_score.max' => 'Le score de fiabilité doit être compris entre 0 et 10.',
        ]);

        try {
            $supplier = Supplier::create([
                'name' => $input['name'],
                'wechat' => $input['wechat'] ?? null,
                'profile' => $input['profile'] ?? null,
                'contact' => $input['contact'] ?? null,
                'accessibility_notes' => $input['accessibility_notes'] ?? null,
                'reliability_score' => $input['reliability_score'] ?? null,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::SUPPLIER_CREATED,
                ' la création du fournisseur via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création du fournisseur : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::SUPPLIER_CREATED,
            "a créé le fournisseur {$supplier->name} via MCP",
            [
                'model_type' => Supplier::class,
                'model_id' => $supplier->id,
                'metadata' => $supplier->toArray(),
            ],
            "fournisseurs/{$supplier->id}"
        );

        return Response::json([
            'message' => 'Fournisseur créé avec succès.',
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'wechat' => $supplier->wechat,
                'contact' => $supplier->contact,
                'reliability_score' => $supplier->reliability_score !== null ? (float) $supplier->reliability_score : null,
                'is_active' => $supplier->is_active,
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
            'name' => $schema->string()
                ->description('Nom du fournisseur (unique).')
                ->required()
                ->max(255),
            'wechat' => $schema->string()
                ->description('Identifiant WeChat du fournisseur.')
                ->max(100),
            'profile' => $schema->string()
                ->description("Profil du fournisseur (spécialités, type de marchandises...).")
                ->max(2000),
            'contact' => $schema->string()
                ->description('Contact (téléphone, email...).')
                ->max(255),
            'accessibility_notes' => $schema->string()
                ->description("Notes d'accessibilité (conditions d'achat, minimums de commande...).")
                ->max(2000),
            'reliability_score' => $schema->number()
                ->description('Score de fiabilité initial de 0 à 10 (optionnel).')
                ->min(0),
        ];
    }
}
