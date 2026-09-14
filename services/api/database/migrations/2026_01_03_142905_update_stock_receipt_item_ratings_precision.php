<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modifier la précision des colonnes
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN attribute_conformity_rating TYPE NUMERIC(4,2)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN quality_rating TYPE NUMERIC(4,2)');

        // Mettre à jour les contraintes de vérification
        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_attribute_conformity_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_quality_rating_check');

        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (attribute_conformity_rating >= 0 AND attribute_conformity_rating <= 10)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_quality_rating_check CHECK (quality_rating >= 0 AND quality_rating <= 10)');
    }

    public function down()
    {
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN attribute_conformity_rating TYPE NUMERIC(3,2)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN quality_rating TYPE NUMERIC(3,2)');

        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_attribute_conformity_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_quality_rating_check');

        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (attribute_conformity_rating >= 0 AND attribute_conformity_rating <= 9.99)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_quality_rating_check CHECK (quality_rating >= 0 AND quality_rating <= 9.99)');
    }
};
