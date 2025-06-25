<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shopping_day_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('shopping_day_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('index');
            $table->decimal('unit_price', 9, 4)->nullable();
            $table->decimal('quantity', 9, 4)->nullable();

            $table->timestamps(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_day_items');
    }
};
