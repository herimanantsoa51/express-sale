<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests HTTP du serveur MCP Express Sale.
 *
 * Aucun de ces tests n'accède à la base de données : la route MCP est protégée
 * par Sanctum et tools/list n'interroge aucune table. L'utilisateur est donc
 * instancié sans persistance, ce qui rend les tests indépendants des migrations.
 */
class ExpressSaleMcpTest extends TestCase
{
    /**
     * La route MCP web est protégée par Sanctum : sans jeton, réponse 401.
     */
    public function test_web_server_requires_authentication(): void
    {
        $response = $this->postJson('/mcp/express-sale', [
            'jsonrpc' => '2.0',
            'id' => 'test-1',
            'method' => 'tools/list',
        ]);

        $response->assertStatus(401);
    }

    /**
     * La route MCP web accepte les appels JSON-RPC authentifiés.
     */
    public function test_web_server_lists_tools_when_authenticated(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/mcp/express-sale', [
            'jsonrpc' => '2.0',
            'id' => 'test-2',
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
    }

    /**
     * La liste des tools MCP expose les lectures et les écritures.
     */
    public function test_web_server_lists_all_tools(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/mcp/express-sale', [
            'jsonrpc' => '2.0',
            'id' => 'test-3',
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);

        // tools/list est paginé (15 tools par page) : suivre les cursors
        $names = [];
        $cursor = null;
        do {
            $payload = [
                'jsonrpc' => '2.0',
                'id' => 'test-3-page',
                'method' => 'tools/list',
            ];
            if ($cursor !== null) {
                $payload['params'] = ['cursor' => $cursor];
            }

            $page = $this->postJson('/mcp/express-sale', $payload);
            $page->assertStatus(200);

            $names = array_merge($names, collect($page->json('result.tools'))->pluck('name')->all());
            $cursor = $page->json('result.nextCursor');
        } while ($cursor !== null);

        $tools = collect($names);

        foreach (['search-products-tool', 'get-product-tool', 'list-customers-tool', 'list-customer-history-tool', 'list-categories-tool', 'create-category-tool', 'list-attribute-types-tool', 'create-attribute-value-tool', 'list-locations-tool', 'manage-location-tool', 'list-accounts-tool', 'list-account-types-tool', 'manage-account-tool', 'list-sales-tool', 'list-stock-receipts-tool', 'list-low-stock-tool', 'list-suppliers-tool', 'list-freight-forwarders-tool', 'list-transactions-tool', 'list-expense-categories-tool', 'manage-planned-expense-tool', 'currency-tool', 'manage-cash-count-tool', 'manage-company-info-tool', 'manage-image-tool', 'generate-document-tool', 'send-document-email-tool', 'sales-report-tool', 'sales-statistics-tool', 'financial-report-tool', 'financial-timeline-tool', 'dashboard-tool', 'stock-movements-report-tool', 'list-activity-logs-tool', 'create-product-tool', 'create-product-variant-tool', 'update-product-tool', 'update-product-variant-tool', 'create-customer-tool', 'update-customer-tool', 'create-sale-tool', 'create-credit-sale-tool', 'create-reservation-tool', 'complete-reservation-tool', 'cancel-reservation-tool', 'pay-credit-installment-tool', 'cancel-sale-tool', 'cancel-credit-tool', 'create-stock-receipt-tool', 'update-stock-receipt-status-tool', 'rate-stock-receipt-tool', 'allocate-stock-receipt-costs-tool', 'pay-stock-receipt-tool', 'stock-transfer-tool', 'stock-adjustment-tool', 'declare-stock-loss-tool', 'create-supplier-tool', 'create-freight-forwarder-tool', 'create-expense-tool', 'transfer-funds-tool', 'cancel-transaction-tool'] as $name) {
            $this->assertTrue($tools->contains($name), "Le tool [{$name}] doit être exposé par le serveur MCP.");
        }
    }

    private function makeUser(): User
    {
        return User::make([
            'name' => 'Admin MCP Test',
            'username' => 'mcp_test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
