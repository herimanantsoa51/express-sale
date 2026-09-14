<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ====================================================================
        // 1. Colonnes autonomes sur RESERVATIONS
        //    (notes N'EXISTE PAS sur reservations, on l'ajoute)
        // ====================================================================
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('reservation_number')->nullable()->after('id');
            $table->unsignedInteger('user_id')->nullable()->after('customer_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->string('payment_method')->nullable()->after('discount_reason');
            $table->text('notes')->nullable()->after('payment_method');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('reservation_number');
        });

        // ====================================================================
        // 2. Colonnes autonomes sur CREDITS
        //    (notes EXISTE DÉJÀ, credit_number/user_id/subtotal/discount non)
        // ====================================================================
        Schema::table('credits', function (Blueprint $table) {
            $table->string('credit_number')->nullable()->after('id');
            $table->unsignedInteger('user_id')->nullable()->after('customer_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->string('payment_method')->nullable()->after('discount_reason');
            // notes existe déjà — on ne l'ajoute PAS

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('credit_number');
        });

        // ====================================================================
        // 3. Table RESERVATION_ITEMS
        //    (sale_items n'a PAS location_id ni discount_amount ni updated_at)
        // ====================================================================
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('reservation_id');
            $table->unsignedInteger('variant_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            $table->index(['reservation_id']);
            $table->index(['variant_id']);
        });

        // ====================================================================
        // 4. Table CREDIT_ITEMS
        // ====================================================================
        Schema::create('credit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('credit_id');
            $table->unsignedInteger('variant_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            $table->index(['credit_id']);
            $table->index(['variant_id']);
        });

        // ====================================================================
        // 5. Table RESERVATION_ITEM_BATCHES (FIFO)
        //    stock_batches.id = bigint, donc unsignedBigInteger
        // ====================================================================
        Schema::create('reservation_item_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_item_id');
            $table->unsignedBigInteger('batch_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('unit_price_at_sale', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->string('status')->default('reserved');
            $table->timestamps();

            $table->foreign('reservation_item_id')->references('id')->on('reservation_items')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('stock_batches')->restrictOnDelete();
            $table->index(['reservation_item_id']);
            $table->index(['batch_id']);
            $table->index(['status']);
        });

        // ====================================================================
        // 6. Table CREDIT_ITEM_BATCHES (FIFO)
        // ====================================================================
        Schema::create('credit_item_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_item_id');
            $table->unsignedBigInteger('batch_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('unit_price_at_sale', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->string('status')->default('sold');
            $table->timestamps();

            $table->foreign('credit_item_id')->references('id')->on('credit_items')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('stock_batches')->restrictOnDelete();
            $table->index(['credit_item_id']);
            $table->index(['batch_id']);
            $table->index(['status']);
        });

        // ====================================================================
        // 7. Ajouter reservation_item_id et credit_item_id sur STOCK_MOVEMENTS
        // ====================================================================
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_item_id')->nullable()->after('sale_id');
            $table->unsignedBigInteger('credit_item_id')->nullable()->after('reservation_item_id');

            $table->foreign('reservation_item_id')->references('id')->on('reservation_items')->nullOnDelete();
            $table->foreign('credit_item_id')->references('id')->on('credit_items')->nullOnDelete();
            $table->index('reservation_item_id');
            $table->index('credit_item_id');
        });

        // ====================================================================
        // 8. Migrer les données existantes
        // ====================================================================

        // 8a. Remplir les colonnes autonomes des reservations
        DB::statement("
            UPDATE reservations r
            SET
                reservation_number = s.sale_number,
                user_id = s.user_id,
                subtotal = s.subtotal,
                discount_amount = s.discount_amount,
                discount_reason = s.discount_reason,
                payment_method = s.payment_method,
                notes = s.notes
            FROM sales s
            WHERE r.sale_id = s.id
              AND s.sale_type = 'reservation'
        ");

        // 8b. Remplir les colonnes autonomes des credits (sauf notes qui existe déjà)
        DB::statement("
            UPDATE credits c
            SET
                credit_number = s.sale_number,
                user_id = s.user_id,
                subtotal = s.subtotal,
                discount_amount = s.discount_amount,
                discount_reason = s.discount_reason,
                payment_method = s.payment_method
            FROM sales s
            WHERE c.sale_id = s.id
              AND s.sale_type = 'credit'
        ");

        // 8c. Migrer sale_items → reservation_items
        //     (sale_items n'a PAS location_id — on met NULL)
        DB::statement('
            INSERT INTO reservation_items (reservation_id, variant_id, location_id, quantity, unit_price, discount_amount, subtotal, created_at, updated_at)
            SELECT
                r.id,
                si.variant_id,
                NULL,
                si.quantity,
                si.unit_price,
                0,
                si.subtotal,
                si.created_at,
                si.created_at
            FROM sale_items si
            JOIN reservations r ON r.sale_id = si.sale_id
        ');

        // 8d. Migrer sale_items → credit_items
        DB::statement('
            INSERT INTO credit_items (credit_id, variant_id, location_id, quantity, unit_price, discount_amount, subtotal, created_at, updated_at)
            SELECT
                c.id,
                si.variant_id,
                NULL,
                si.quantity,
                si.unit_price,
                0,
                si.subtotal,
                si.created_at,
                si.created_at
            FROM sale_items si
            JOIN credits c ON c.sale_id = si.sale_id
        ');

        // 8e. Migrer sale_item_batches → reservation_item_batches
        //     (sale_item_batches n'a PAS unit_cost ni profit — on calcule depuis stock_batches)
        DB::statement('
            INSERT INTO reservation_item_batches (reservation_item_id, batch_id, quantity, unit_cost, unit_price_at_sale, discount_amount, profit, status, created_at, updated_at)
            SELECT
                ri.id,
                sib.batch_id,
                sib.quantity,
                COALESCE(sb.total_unit_cost, 0),
                sib.unit_price_at_sale,
                COALESCE(sib.discount_at_sale, 0),
                COALESCE((sib.unit_price_at_sale - COALESCE(sb.total_unit_cost, 0)) * sib.quantity - COALESCE(sib.discount_at_sale, 0), 0),
                sib.status,
                sib.created_at,
                COALESCE(sib.updated_at, sib.created_at)
            FROM sale_item_batches sib
            JOIN sale_items si ON si.id = sib.sale_item_id
            JOIN reservations r ON r.sale_id = si.sale_id
            JOIN reservation_items ri ON ri.reservation_id = r.id AND ri.variant_id = si.variant_id
            JOIN stock_batches sb ON sb.id = sib.batch_id
        ');

        // 8f. Migrer sale_item_batches → credit_item_batches
        DB::statement('
            INSERT INTO credit_item_batches (credit_item_id, batch_id, quantity, unit_cost, unit_price_at_sale, discount_amount, profit, status, created_at, updated_at)
            SELECT
                ci.id,
                sib.batch_id,
                sib.quantity,
                COALESCE(sb.total_unit_cost, 0),
                sib.unit_price_at_sale,
                COALESCE(sib.discount_at_sale, 0),
                COALESCE((sib.unit_price_at_sale - COALESCE(sb.total_unit_cost, 0)) * sib.quantity - COALESCE(sib.discount_at_sale, 0), 0),
                sib.status,
                sib.created_at,
                COALESCE(sib.updated_at, sib.created_at)
            FROM sale_item_batches sib
            JOIN sale_items si ON si.id = sib.sale_item_id
            JOIN credits c ON c.sale_id = si.sale_id
            JOIN credit_items ci ON ci.credit_id = c.id AND ci.variant_id = si.variant_id
            JOIN stock_batches sb ON sb.id = sib.batch_id
        ');

        // 8g. Rendre sale_id nullable sur reservations et credits (transition)
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('sale_id')->nullable()->change();
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->unsignedInteger('sale_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Remettre sale_id NOT NULL
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('sale_id')->nullable(false)->change();
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->unsignedInteger('sale_id')->nullable(false)->change();
        });

        // Supprimer colonnes stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['reservation_item_id']);
            $table->dropForeign(['credit_item_id']);
            $table->dropColumn(['reservation_item_id', 'credit_item_id']);
        });

        Schema::dropIfExists('credit_item_batches');
        Schema::dropIfExists('reservation_item_batches');
        Schema::dropIfExists('credit_items');
        Schema::dropIfExists('reservation_items');

        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['credit_number']);
            $table->dropColumn(['credit_number', 'user_id', 'subtotal', 'discount_amount', 'discount_reason', 'payment_method']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['reservation_number']);
            $table->dropColumn(['reservation_number', 'user_id', 'subtotal', 'discount_amount', 'discount_reason', 'payment_method', 'notes']);
        });
    }
};
