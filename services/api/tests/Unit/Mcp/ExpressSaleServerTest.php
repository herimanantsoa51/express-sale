<?php

namespace Tests\Unit\Mcp;

use App\Mcp\Servers\ExpressSaleServer;
use App\Mcp\Tools\CreateCreditSaleTool;
use App\Mcp\Tools\CreateCustomerTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreateProductVariantTool;
use App\Mcp\Tools\CreateReservationTool;
use App\Mcp\Tools\CreateSaleTool;
use App\Mcp\Tools\GetProductTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\SalesStatisticsTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\UpdateCustomerTool;
use App\Mcp\Tools\UpdateProductTool;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantLocation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests des tools MCP du serveur Express Sale.
 */
class ExpressSaleServerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Identifiant du produit créé par seedData().
     */
    protected int $productId = 0;

    /**
     * Peuple la base de test avec des données minimales.
     */
    private function seedData(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Électronique']);

        $product = Product::create([
            'name' => 'Téléphone Test',
            'description' => 'Un téléphone pour les tests.',
            'category_id' => $category->id,
            'base_price' => 250.00,
            'is_active' => true,
        ]);
        $this->productId = (int) $product->id;

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-TEST-001',
            'price_adjustment' => 0,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
            'low_stock_threshold' => 3,
            'available_quantity' => 8,
            'credit_quantity' => 0,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Client Test',
            'phone' => '0340000000',
            'address' => 'Antananarivo',
            'customer_number' => 'C-0001',
            'reliability_score' => 8.5,
            'loyalty_points' => 10,
            'credit_limit' => 500000,
            'is_active' => true,
        ]);

        $sale = Sale::create([
            'sale_number' => 'V-0001',
            'user_id' => $user->id,
            'sale_date' => now(),
            'sale_type' => 'immediate',
            'subtotal' => 300.00,
            'total_amount' => 300.00,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'status' => 'CONFIRMED',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);
    }

    /**
     * Le tool de recherche de produits retourne les produits correspondants.
     */
    public function test_search_products_tool(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(SearchProductsTool::class, [
            'query' => 'Télé',
        ]);

        $response
            ->assertOk()
            ->assertSee('Téléphone Test')
            ->assertSee('Électronique');
    }

    /**
     * Le tool de recherche respecte le filtre d'activité.
     */
    public function test_search_products_tool_excludes_inactive_products(): void
    {
        $this->seedData();

        $category = Category::create(['name' => 'Vêtements']);
        Product::create([
            'name' => 'Chemise Cachée',
            'category_id' => $category->id,
            'base_price' => 20.00,
            'is_active' => false,
        ]);

        $response = ExpressSaleServer::tool(SearchProductsTool::class, [
            'query' => 'Chemise',
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertDontSee('Chemise Cachée');
    }

    /**
     * Le tool de détail produit retourne les variantes et le stock.
     */
    public function test_get_product_tool(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(GetProductTool::class, [
            'id' => $this->productId,
        ]);

        $response
            ->assertOk()
            ->assertSee('Téléphone Test')
            ->assertSee('SKU-TEST-001');
    }

    /**
     * Le tool de détail produit retourne une erreur pour un id inconnu.
     */
    public function test_get_product_tool_returns_error_for_unknown_product(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(GetProductTool::class, [
            'id' => 99999,
        ]);

        $response->assertHasErrors();
    }

    /**
     * Le tool de recherche de clients retourne les clients correspondants.
     */
    public function test_list_customers_tool(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(ListCustomersTool::class, [
            'query' => 'Client',
        ]);

        $response
            ->assertOk()
            ->assertSee('Client Test')
            ->assertSee('0340000000');
    }

    /**
     * Le tool de statistiques retourne le CA, le nombre de ventes et le top produits.
     */
    public function test_sales_statistics_tool(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(SalesStatisticsTool::class, []);

        $response
            ->assertOk()
            ->assertSee('total_revenue')
            ->assertSee('Téléphone Test');
    }

    /**
     * Peuple les données nécessaires à une vente immédiate via MCP.
     *
     * @return array{user: User, variant: ProductVariant, location: Location, account: Account}
     */
    private function seedSaleData(): array
    {
        $user = User::create([
            'name' => 'Vendeur Test',
            'username' => 'vendeur_test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Alimentation']);

        $product = Product::create([
            'name' => 'Riz Parfumé 5kg',
            'category_id' => $category->id,
            'base_price' => 250.00,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-RIZ-5KG',
            'price_adjustment' => 0,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'available_quantity' => 10,
            'credit_quantity' => 0,
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Dépôt Principal',
            'code' => 'DEPOT-001',
        ]);

        ProductVariantLocation::create([
            'variant_id' => $variant->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        StockBatch::create([
            'variant_id' => $variant->id,
            'batch_number' => 'B-'.Str::random(8),
            'initial_quantity' => 10,
            'remaining_quantity' => 10,
            'supplier_unit_cost' => 150.00,
            'freight_cost_per_unit' => 10.00,
            'other_costs_per_unit' => 5.00,
            'total_unit_cost' => 165.00,
            'cost_status' => 'validated',
        ]);

        // La table account_types n'a pas de colonne updated_at, donc on insère via le query builder
        $accountTypeId = DB::table('account_types')->insertGetId([
            'code' => 'CASH_TEST',
            'name' => 'Caisse test',
            'display_name' => 'Caisse principale de test',
        ]);

        $account = Account::create([
            'account_type_id' => $accountTypeId,
            'name' => 'Caisse principale',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        // Le type INCOME est généralement déjà présent en base (seeder), sinon on le crée
        if (! TransactionType::where('code', 'INCOME')->exists()) {
            TransactionType::create([
                'code' => 'INCOME',
                'name' => 'Revenu',
                'display_name' => 'Vente / Revenu',
                'category' => 'income',
            ]);
        }

        return [
            'user' => $user,
            'variant' => $variant,
            'location' => $location,
            'account' => $account,
        ];
    }

    /**
     * Le tool de réservation crée la réservation et encaisse l'acompte.
     */
    public function test_create_reservation_tool(): void
    {
        $seed = $this->seedSaleData();
        $user = $seed['user'];
        $variant = $seed['variant'];
        $location = $seed['location'];
        $account = $seed['account'];

        $customer = Customer::create([
            'name' => 'Client Réservation',
            'phone' => '0344444444',
            'customer_number' => 'C-RESERV',
            'is_active' => true,
        ]);

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateReservationTool::class, [
                'customer_id' => (int) $customer->id,
                'expiry_date' => now()->addDays(7)->toDateString(),
                'deposit_amount' => 100.00,
                'account_id' => (int) $account->id,
                'payment_method' => 'cash',
                'items' => [
                    [
                        'variant_id' => (int) $variant->id,
                        'location_id' => (int) $location->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertSee('Réservation créée avec succès');

        // La réservation est persistée avec l'acompte et le restant dû
        $reservation = \App\Models\Reservation::where('customer_id', $customer->id)->first();
        $this->assertTrue($reservation !== null);
        $this->assertEquals('500.00', (string) $reservation->total_amount);
        $this->assertEquals('100.00', (string) $reservation->deposit_amount);
        $this->assertEquals('400.00', (string) $reservation->remaining_amount);

        // L'acompte est encaissé sur le compte
        $account->refresh();
        $this->assertEquals('100.00', (string) $account->current_balance);

        // Le stock FIFO est réservé (10 → 8) et synchronisé sur la variante
        $batch = StockBatch::where('variant_id', $variant->id)->first();
        $this->assertEquals(8, (int) $batch->remaining_quantity);
        $variant->refresh();
        $this->assertEquals(8, (int) $variant->stock_quantity);
    }

    /**
     * Le tool de réservation exige un compte quand un acompte est versé.
     */
    public function test_create_reservation_tool_requires_account_for_deposit(): void
    {
        $seed = $this->seedSaleData();
        $user = $seed['user'];
        $variant = $seed['variant'];
        $location = $seed['location'];

        $customer = Customer::create([
            'name' => 'Client Sans Compte',
            'phone' => '0345555555',
            'customer_number' => 'C-NOACCOUNT',
            'is_active' => true,
        ]);

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateReservationTool::class, [
                'customer_id' => (int) $customer->id,
                'expiry_date' => now()->addDays(7)->toDateString(),
                'deposit_amount' => 50.00,
                'items' => [
                    [
                        'variant_id' => (int) $variant->id,
                        'location_id' => (int) $location->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertHasErrors();
        $this->assertTrue(\App\Models\Reservation::where('customer_id', $customer->id)->doesntExist());
    }

    /**
     * Le tool de vente à crédit crée le crédit, débite le stock et génère les échéances.
     */
    public function test_create_credit_sale_tool(): void
    {
        $seed = $this->seedSaleData();
        $user = $seed['user'];
        $variant = $seed['variant'];
        $location = $seed['location'];

        $customer = Customer::create([
            'name' => 'Client Crédit',
            'phone' => '0342222222',
            'customer_number' => 'C-CREDIT',
            'credit_limit' => 500000,
            'is_active' => true,
        ]);

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateCreditSaleTool::class, [
                'customer_id' => (int) $customer->id,
                'due_date' => now()->addMonth()->toDateString(),
                'installment_count' => 2,
                'installment_frequency' => 'monthly',
                'items' => [
                    [
                        'variant_id' => (int) $variant->id,
                        'location_id' => (int) $location->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertSee('Vente à crédit créée avec succès');

        // Le crédit est persisté : 2 × 250, rien n'est payé
        $credit = \App\Models\Credit::where('customer_id', $customer->id)->first();
        $this->assertTrue($credit !== null);
        $this->assertEquals('500.00', (string) $credit->total_amount);
        $this->assertEquals('500.00', (string) $credit->amount_due);
        $this->assertEquals('active', $credit->status);

        // 2 échéances générées
        $this->assertEquals(2, $credit->installments()->count());

        // Le stock FIFO est débité (10 → 8) et synchronisé sur la variante
        $batch = StockBatch::where('variant_id', $variant->id)->first();
        $this->assertEquals(8, (int) $batch->remaining_quantity);
        $variant->refresh();
        $this->assertEquals(8, (int) $variant->stock_quantity);
    }

    /**
     * Le tool de vente à crédit refuse un client au-dessus de sa limite.
     */
    public function test_create_credit_sale_tool_rejects_insufficient_credit_limit(): void
    {
        $seed = $this->seedSaleData();
        $user = $seed['user'];
        $variant = $seed['variant'];
        $location = $seed['location'];

        $customer = Customer::create([
            'name' => 'Client Limite Basse',
            'phone' => '0343333333',
            'customer_number' => 'C-LIMITE',
            'credit_limit' => 100,
            'is_active' => true,
        ]);

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateCreditSaleTool::class, [
                'customer_id' => (int) $customer->id,
                'due_date' => now()->addMonth()->toDateString(),
                'items' => [
                    [
                        'variant_id' => (int) $variant->id,
                        'location_id' => (int) $location->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertHasErrors();
        $this->assertTrue(\App\Models\Credit::where('customer_id', $customer->id)->doesntExist());
    }

    /**
     * Le tool crée un client avec un numéro généré automatiquement.
     */
    public function test_create_customer_tool(): void
    {
        $this->seedData();
        $user = User::first();

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateCustomerTool::class, [
                'name' => 'Andry Rakoto',
                'phone' => '0341111111',
                'address' => 'Antananarivo',
            ]);

        $response
            ->assertOk()
            ->assertSee('Andry Rakoto')
            ->assertSee('CL-');

        $customer = Customer::where('phone', '0341111111')->first();
        $this->assertTrue($customer !== null);
        $this->assertTrue(str_starts_with($customer->customer_number, 'CL-'));
    }

    /**
     * Le tool de création de client refuse un téléphone déjà utilisé.
     */
    public function test_create_customer_tool_rejects_duplicate_phone(): void
    {
        $this->seedData();
        $user = User::first();

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateCustomerTool::class, [
                'name' => 'Doublon',
                'phone' => '0340000000',
            ]);

        $response->assertHasErrors();
    }

    /**
     * Le tool met à jour un client existant.
     */
    public function test_update_customer_tool(): void
    {
        $this->seedData();
        $user = User::first();
        $customer = Customer::where('phone', '0340000000')->firstOrFail();

        $response = ExpressSaleServer::actingAs($user)
            ->tool(UpdateCustomerTool::class, [
                'id' => (int) $customer->id,
                'name' => 'Client Test Modifié',
                'reliability_score' => 9.0,
            ]);

        $response
            ->assertOk()
            ->assertSee('Client Test Modifié');

        $customer->refresh();
        $this->assertEquals('9.00', (string) $customer->reliability_score);
    }

    /**
     * Le tool de vente immédiate crée la vente, débite le stock FIFO et crédite le compte.
     */
    public function test_create_sale_tool(): void
    {
        $seed = $this->seedSaleData();
        $user = $seed['user'];
        $variant = $seed['variant'];
        $location = $seed['location'];
        $account = $seed['account'];

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateSaleTool::class, [
                'account_id' => (int) $account->id,
                'payment_method' => 'cash',
                'items' => [
                    [
                        'variant_id' => (int) $variant->id,
                        'location_id' => (int) $location->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertSee('Vente créée avec succès')
            ->assertSee('500');

        // La vente est persistée avec le bon total (2 × 250)
        $sale = Sale::where('total_amount', 500.00)->first();
        $this->assertTrue($sale !== null);

        // Le stock FIFO et l'emplacement sont débités
        $batch = StockBatch::where('variant_id', $variant->id)->first();
        $this->assertEquals(8, (int) $batch->remaining_quantity);

        $pvl = ProductVariantLocation::where('variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->first();
        $this->assertEquals(8, (int) $pvl->quantity);

        // La transaction comptable crédite le compte de 500 Ar
        $account->refresh();
        $this->assertEquals('500.00', (string) $account->current_balance);

        $this->assertTrue(
            AccountTransaction::where('sale_id', $sale->id)->exists(),
            'La transaction comptable liée à la vente doit exister.'
        );

        $this->assertTrue(
            StockMovement::where('variant_id', $variant->id)->exists(),
            'Le mouvement de stock doit exister.'
        );
    }

    /**
     * Le tool de vente valide la présence d'un compte et d'articles.
     */
    public function test_create_sale_tool_validates_required_fields(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(CreateSaleTool::class, []);

        $response->assertHasErrors();
    }

    /**
     * Le tool crée un produit et le persiste en base.
     */
    public function test_create_product_tool(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'create_product',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Accessoires']);

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateProductTool::class, [
                'name' => 'Écouteurs Bluetooth',
                'category_id' => (int) $category->id,
                'base_price' => 45000.00,
                'description' => 'Sans fil',
            ]);

        $response
            ->assertOk()
            ->assertSee('Écouteurs Bluetooth');

        $product = Product::where('name', 'Écouteurs Bluetooth')->first();
        $this->assertTrue($product !== null);
        $this->assertEquals('45000.00', (string) $product->base_price);
    }

    /**
     * Le tool de création de produit valide les champs requis.
     */
    public function test_create_product_tool_validates_required_fields(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(CreateProductTool::class, [
            'name' => 'Produit sans catégorie',
        ]);

        $response->assertHasErrors();
    }

    /**
     * Le tool met à jour un produit existant.
     */
    public function test_update_product_tool(): void
    {
        $this->seedData();
        $user = User::first();

        $response = ExpressSaleServer::actingAs($user)
            ->tool(UpdateProductTool::class, [
                'id' => $this->productId,
                'name' => 'Téléphone Test Pro',
                'base_price' => 300.00,
            ]);

        $response
            ->assertOk()
            ->assertSee('Téléphone Test Pro');

        $product = Product::find($this->productId);
        $this->assertEquals('300.00', (string) $product->base_price);
    }

    /**
     * Le tool de mise à jour retourne une erreur pour un produit inconnu.
     */
    public function test_update_product_tool_returns_error_for_unknown_product(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(UpdateProductTool::class, [
            'id' => 99999,
            'name' => 'Introuvable',
        ]);

        $response->assertHasErrors();
    }

    /**
     * Le tool crée une variante avec ses attributs et un SKU généré.
     */
    public function test_create_product_variant_tool(): void
    {
        $this->seedData();
        $user = User::first();

        $response = ExpressSaleServer::actingAs($user)
            ->tool(CreateProductVariantTool::class, [
                'product_id' => $this->productId,
                'attributes' => [
                    ['attribute_type_id' => 16, 'value' => 'Noir'],
                    ['attribute_type_id' => 17, 'value' => '34'],
                ],
            ]);

        $response
            ->assertOk()
            ->assertSee('Variante créée avec succès')
            ->assertSee('Noir');

        $product = Product::find($this->productId);
        $variant = $product->variants()->latest('id')->first();
        $this->assertTrue($variant !== null);
        $this->assertTrue($variant->sku !== null && $variant->sku !== '');
        $this->assertEquals(2, $variant->variantAttributeValues()->count());
        $values = $variant->variantAttributeValues()->with('attributeValue')
            ->get()->map(fn ($vav) => $vav->attributeValue->value)->sort()->values()->all();
        $this->assertEquals(['34', 'Noir'], $values);
    }

    /**
     * Le tool de création de variante valide le produit et les attributs.
     */
    public function test_create_product_variant_tool_validates_required_fields(): void
    {
        $this->seedData();

        $response = ExpressSaleServer::tool(CreateProductVariantTool::class, [
            'product_id' => $this->productId,
        ]);

        $response->assertHasErrors();
    }

    /**
     * Le tool de création de variante retourne une erreur pour un produit inconnu.
     */
    public function test_create_product_variant_tool_returns_error_for_unknown_product(): void
    {
        $response = ExpressSaleServer::tool(CreateProductVariantTool::class, [
            'product_id' => 99999,
            'attributes' => [
                ['attribute_type_id' => 16, 'value' => 'Noir'],
            ],
        ]);

        $response->assertHasErrors();
    }
}
