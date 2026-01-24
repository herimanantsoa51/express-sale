<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter quality_rating et quality_notes sur stock_receipt_items
        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->decimal('quality_rating', 4, 2)->nullable()->after('notes');
            $table->text('quality_notes')->nullable()->after('quality_rating');
        });

        // 2. Migrer les données existantes vers la nouvelle structure
        // Récupérer tous les ratings avec quality_rating
        $existingRatings = DB::table('stock_receipt_item_ratings')
            ->select('stock_receipt_item_id', 'quality_rating', 'quality_notes')
            ->whereNotNull('quality_rating')
            ->get()
            ->groupBy('stock_receipt_item_id');

        // Mettre à jour les stock_receipt_items avec les quality_rating
        foreach ($existingRatings as $itemId => $ratings) {
            $firstRating = $ratings->first();
            DB::table('stock_receipt_items')
                ->where('id', $itemId)
                ->update([
                    'quality_rating' => $firstRating->quality_rating,
                    'quality_notes' => $firstRating->quality_notes
                ]);
        }

        // 3. Supprimer les ratings sans attribute_type_id (ratings généraux)
        // Ils sont maintenant sur stock_receipt_items
        DB::table('stock_receipt_item_ratings')
            ->whereNull('attribute_type_id')
            ->delete();

        // 4. Modifier stock_receipt_item_ratings
        Schema::table('stock_receipt_item_ratings', function (Blueprint $table) {
            // Renommer attribute_conformity_rating en conformity_rating
            $table->renameColumn('attribute_conformity_rating', 'conformity_rating');
            
            // Supprimer quality_rating (maintenant sur l'item)
            $table->dropColumn('quality_rating');
            
            // Renommer quality_notes en notes
            $table->renameColumn('quality_notes', 'notes');
        });

        // 5. Maintenant qu'il n'y a plus de NULL, rendre attribute_type_id obligatoire
        Schema::table('stock_receipt_item_ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Remettre attribute_type_id nullable
        Schema::table('stock_receipt_item_ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_type_id')->nullable()->change();
        });

        // Restaurer les colonnes
        Schema::table('stock_receipt_item_ratings', function (Blueprint $table) {
            $table->renameColumn('conformity_rating', 'attribute_conformity_rating');
            $table->decimal('quality_rating', 4, 2)->nullable()->after('attribute_conformity_rating');
            $table->renameColumn('notes', 'quality_notes');
        });

        // Migrer les données en sens inverse
        $items = DB::table('stock_receipt_items')
            ->whereNotNull('quality_rating')
            ->get();

        foreach ($items as $item) {
            // Créer un rating général pour chaque item qui avait une quality_rating
            DB::table('stock_receipt_item_ratings')->insert([
                'stock_receipt_item_id' => $item->id,
                'attribute_type_id' => null,
                'attribute_conformity_rating' => 5.0,
                'quality_rating' => $item->quality_rating,
                'quality_notes' => $item->quality_notes,
                'rated_by' => 1, // Vous devrez ajuster selon votre logique
                'created_at' => now()
            ]);
        }

        // Supprimer les colonnes de stock_receipt_items
        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['quality_rating', 'quality_notes']);
        });
    }
};