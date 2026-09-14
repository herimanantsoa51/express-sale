<?php

namespace App\Mcp\Tools;

use App\Models\AccountType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Liste les types de comptes de trésorerie (caisse, mobile money, banque...) avec leurs codes. Nécessaire pour trouver l'account_type_id avant de créer un compte via create-account-tool.")]
class ListAccountTypesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $types = AccountType::query()->orderBy('id')->get(['id', 'code', 'display_name', 'is_active']);

        return Response::json([
            'total' => $types->count(),
            'account_types' => $types->map(fn (AccountType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'display_name' => $type->display_name,
                'is_active' => $type->is_active,
            ])->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
