<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CompanyInfoController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Coordonnées de la boutique utilisées sur les factures et documents : action=get pour consulter, action=update pour modifier (nom, adresse, téléphone, email, signature de facture).')]
class ManageCompanyInfoTool extends Tool
{
    public function handle(Request $request, CompanyInfoController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:get,update',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo_path' => 'nullable|string|max:255',
            'invoice_signature' => 'nullable|string|max:1000',
        ], [
            'action.required' => "L'action est requise (action) : get ou update.",
            'email.email' => "L'adresse email est invalide.",
        ]);

        if ($input['action'] === 'get') {
            return Response::json($controller->index()->getData(true));
        }

        if (empty($input['name'])) {
            return Response::error('Le nom de la boutique est requis pour la mise à jour (name).');
        }

        try {
            $response = $controller->update(HttpRequest::create('/', 'PUT', array_filter([
                'name' => $input['name'],
                'address' => $input['address'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'logo_path' => $input['logo_path'] ?? null,
                'invoice_signature' => $input['invoice_signature'] ?? null,
            ], fn ($v) => $v !== null)));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json([
            'message' => 'Informations de la boutique mises à jour avec succès.',
            'company' => $response->getData(true),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'get' (consulter) ou 'update' (modifier).")
                ->required()
                ->enum(['get', 'update']),
            'name' => $schema->string()
                ->description('Nom de la boutique (requis pour update).')
                ->max(255),
            'address' => $schema->string()
                ->description('Adresse.')
                ->max(500),
            'phone' => $schema->string()
                ->description('Téléphone.')
                ->max(50),
            'email' => $schema->string()
                ->description('Email de contact.')
                ->max(255),
            'logo_path' => $schema->string()
                ->description("Chemin du logo (voir manage-image-tool).")
                ->max(255),
            'invoice_signature' => $schema->string()
                ->description('Signature apparaissant sur les factures.')
                ->max(1000),
        ];
    }
}
