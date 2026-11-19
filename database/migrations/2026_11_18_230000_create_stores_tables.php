<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each store has its own shopping order (its layout). A shopping day may
     * belong to a store so its items follow that order.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name');

            $table->timestamps(3);
        });

        Schema::create('store_product', function (Blueprint $table) {
            $table->foreignUuid('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->integer('position');

            $table->primary(['store_id', 'product_id']);
        });

        Schema::table('shopping_days', function (Blueprint $table) {
            $table->foreignUuid('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shopping_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::dropIfExists('store_product');
        Schema::dropIfExists('stores');
    }
};
