<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Freight Forwarders - service_score
        DB::statement('ALTER TABLE freight_forwarders ALTER COLUMN service_score TYPE NUMERIC(4,2)');

        // 2. Suppliers - reliability_score (si existe)
        DB::statement('ALTER TABLE suppliers ALTER COLUMN reliability_score TYPE NUMERIC(4,2)');

        // 3. Stock Receipt Item Ratings
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN attribute_conformity_rating TYPE NUMERIC(4,2)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN quality_rating TYPE NUMERIC(4,2)');

        // Mettre à jour les contraintes
        $this->updateConstraints();
    }

    public function down()
    {
        DB::statement('ALTER TABLE freight_forwarders ALTER COLUMN service_score TYPE NUMERIC(3,2)');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN reliability_score TYPE NUMERIC(3,2)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN attribute_conformity_rating TYPE NUMERIC(3,2)');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ALTER COLUMN quality_rating TYPE NUMERIC(3,2)');

        $this->revertConstraints();
    }

    private function updateConstraints()
    {
        // Freight Forwarders
        DB::statement('ALTER TABLE freight_forwarders DROP CONSTRAINT IF EXISTS freight_forwarders_service_score_check');
        DB::statement('ALTER TABLE freight_forwarders ADD CONSTRAINT freight_forwarders_service_score_check CHECK (service_score >= 0 AND service_score <= 10)');

        // Suppliers
        DB::statement('ALTER TABLE suppliers DROP CONSTRAINT IF EXISTS suppliers_reliability_score_check');
        DB::statement('ALTER TABLE suppliers ADD CONSTRAINT suppliers_reliability_score_check CHECK (reliability_score >= 0 AND reliability_score <= 10)');

        // Stock Receipt Item Ratings
        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_attribute_conformity_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (attribute_conformity_rating >= 0 AND attribute_conformity_rating <= 10)');

        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_quality_rating_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_quality_rating_check CHECK (quality_rating >= 0 AND quality_rating <= 10)');
    }

    private function revertConstraints()
    {
        DB::statement('ALTER TABLE freight_forwarders DROP CONSTRAINT IF EXISTS freight_forwarders_service_score_check');
        DB::statement('ALTER TABLE freight_forwarders ADD CONSTRAINT freight_forwarders_service_score_check CHECK (service_score >= 0 AND service_score <= 9.99)');

        DB::statement('ALTER TABLE suppliers DROP CONSTRAINT IF EXISTS suppliers_reliability_score_check');
        DB::statement('ALTER TABLE suppliers ADD CONSTRAINT suppliers_reliability_score_check CHECK (reliability_score >= 0 AND reliability_score <= 9.99)');

        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_attribute_conformity_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (attribute_conformity_rating >= 0 AND attribute_conformity_rating <= 9.99)');

        DB::statement('ALTER TABLE stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_quality_rating_check');
        DB::statement('ALTER TABLE stock_receipt_item_ratings ADD CONSTRAINT stock_receipt_item_ratings_quality_rating_check CHECK (quality_rating >= 0 AND quality_rating <= 9.99)');
    }
};
