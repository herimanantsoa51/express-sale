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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->onDelete('cascade');
            
            $table->foreignId('stock_receipt_item_id')
                ->nullable()
                ->constrained('stock_receipt_items')
                ->onDelete('set null');
            
            // Identification du lot
            $table->string('batch_number', 50)->unique();
            
            // Quantités
            $table->unsignedInteger('initial_quantity')
                ->comment('Quantité initiale du lot');
            
            $table->unsignedInteger('remaining_quantity')
                ->default(0)
                ->comment('Quantité restante disponible');
            
            // ===== COÛTS UNITAIRES =====
            
            // 1. Coût fournisseur (de base)
            $table->decimal('supplier_unit_cost', 12, 2)
                ->comment('Coût unitaire payé au fournisseur (depuis stock_receipt_items)');
            
            // 2. Coûts additionnels répartis
            $table->decimal('freight_cost_per_unit', 12, 2)
                ->default(0)
                ->comment('Frais de transport par unité (répartis)');
            
            $table->decimal('other_costs_per_unit', 12, 2)
                ->default(0)
                ->comment('Autres frais par unité (manutention, stockage, etc.)');
            
            // 3. Coût total unitaire (calculé)
            $table->decimal('total_unit_cost', 12, 2)
                ->storedAs('supplier_unit_cost + freight_cost_per_unit + other_costs_per_unit')
                ->comment('Coût unitaire total = fournisseur + tous les frais');
            
            // ===== STATUT DE VALIDATION =====
            
            $table->enum('cost_status', ['pending', 'estimated', 'validated'])
                ->default('pending')
                ->comment('pending = pas réparti, estimated = recommandation calculée, validated = validé manuellement');
            
            // ===== DATES & TRAÇABILITÉ =====
            
            $table->timestamp('received_date')
                ->useCurrent()
                ->comment('Date de réception du lot');
            
            $table->timestamp('cost_validated_at')
                ->nullable()
                ->comment('Date de validation des coûts');
            
            $table->foreignId('cost_validated_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Utilisateur ayant validé les coûts');
            
            $table->timestamps();
            
            // ===== INDEX =====
            
            // Pour FIFO (chercher les plus anciens avec stock)
            $table->index(['variant_id', 'remaining_quantity'], 'idx_variant_remaining');
            
            // Pour tri FIFO (date puis id)
            $table->index(['variant_id', 'received_date', 'id'], 'idx_fifo_order');
            
            // Pour recherche par statut de coût
            $table->index('cost_status', 'idx_cost_status');
        });

        // ===== CONTRAINTES =====
        
        DB::statement("
            ALTER TABLE stock_batches
            ADD CONSTRAINT chk_batch_remaining_positive 
                CHECK (remaining_quantity >= 0),
            ADD CONSTRAINT chk_batch_remaining_lte_initial 
                CHECK (remaining_quantity <= initial_quantity),
            ADD CONSTRAINT chk_batch_supplier_cost_positive 
                CHECK (supplier_unit_cost > 0),
            ADD CONSTRAINT chk_batch_costs_non_negative 
                CHECK (
                    freight_cost_per_unit >= 0 AND 
                    other_costs_per_unit >= 0
                )
        ");

        // ===== COMMENTAIRES =====
        
        DB::statement("COMMENT ON TABLE stock_batches IS 'Lots de stock pour tracking FIFO des coûts réels'");
        DB::statement("COMMENT ON COLUMN stock_batches.remaining_quantity IS 'Quantité encore disponible (diminue à chaque vente FIFO)'");
        DB::statement("COMMENT ON COLUMN stock_batches.total_unit_cost IS 'Coût complet par unité utilisé pour calcul des bénéfices'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};