<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('product_searches');
        Schema::dropIfExists('products');

        Schema::dropIfExists('commerce_sale_items');
        Schema::dropIfExists('commerce_sales');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('commerce_purchase_items');
        Schema::dropIfExists('commerce_purchases');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('commerce_products');
        Schema::dropIfExists('commerce_product_categories');
        Schema::dropIfExists('commerce_users');
        Schema::dropIfExists('commerces');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Les catalogues, ventes et commerces ne sont plus recréés.
    }
};
