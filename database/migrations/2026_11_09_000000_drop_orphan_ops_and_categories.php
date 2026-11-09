<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('announcements');
        Schema::dropIfExists('market_visits');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('market_product_category');
        Schema::dropIfExists('product_categories');

        Schema::enableForeignKeyConstraints();

        if (Schema::hasColumn('places', 'product_category_ids')) {
            Schema::table('places', function (Blueprint $table) {
                $table->dropColumn('product_category_ids');
            });
        }

        if (Schema::hasColumn('place_requests', 'product_category_ids')) {
            Schema::table('place_requests', function (Blueprint $table) {
                $table->dropColumn('product_category_ids');
            });
        }

        $this->addIndexIfMissing('payment_receipts', ['market_id', 'status'], 'payment_receipts_market_id_status_index');
        $this->addIndexIfMissing('places', ['market_id', 'chief_user_id'], 'places_market_id_chief_user_id_index');
        $this->addIndexIfMissing('place_requests', ['market_id', 'status'], 'place_requests_market_id_status_index');
    }

    public function down(): void
    {
        // Orphan / category tables are not recreated.
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        $indexes = Schema::getIndexes($tableName);

        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return;
            }
            if (($index['columns'] ?? []) === $columns) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }
};
