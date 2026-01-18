<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_item_batches', function (Blueprint $table) {
            $table->id();
            
            // ===== RELATIONS =====
            
            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->onDelete('cascade')
                ->comment('Article vendu');
            
            $table->foreignId('batch_id')
                ->constrained('stock_batches')
                ->onDelete('restrict')
                ->comment('Lot de stock utilisé (FIFO)');
            
            // ===== QUANTITÉ =====
            
            $table->unsignedInteger('quantity')
                ->comment('Quantité vendue provenant de ce lot');
            
            // ===== PRIX DE VENTE (snapshot nécessaire) =====
            
            $table->decimal('unit_price_at_sale', 12, 2)
                ->comment('Prix de vente unitaire AU MOMENT de la vente (peut changer après)');
            
            // ===== NOTE =====
            // Le coût est dans batch.total_unit_cost (pas de snapshot)
            // ⚠️ Si batch.total_unit_cost change, les calculs changent aussi
            
            // ===== DATES =====
            
            $table->timestamps();
            
            // ===== INDEX =====
            
            $table->index('sale_item_id', 'idx_sib_sale_item');
            $table->index('batch_id', 'idx_sib_batch');
            $table->index('created_at', 'idx_sib_date');
        });

        // ===== CONTRAINTES =====
        
        DB::statement("
            ALTER TABLE sale_item_batches
            ADD CONSTRAINT chk_sib_quantity_positive 
                CHECK (quantity > 0),
            ADD CONSTRAINT chk_sib_price_positive 
                CHECK (unit_price_at_sale >= 0)
        ");

        // ===== COMMENTAIRES =====
        
        DB::statement("
            COMMENT ON TABLE sale_item_batches IS 
            'Traçabilité FIFO : quel batch a été vendu dans quelle vente (sans snapshot de coût)'
        ");
        
        DB::statement("
            COMMENT ON COLUMN sale_item_batches.unit_price_at_sale IS 
            'Snapshot du prix de vente (le coût est lu depuis batch.total_unit_cost)'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_item_batches');
    }
};