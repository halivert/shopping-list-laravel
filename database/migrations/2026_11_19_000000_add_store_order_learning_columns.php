<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The store order is learned from the order in which items are priced
     * while shopping (see App\Stores\LearnStoreOrder).
     */
    public function up(): void
    {
        Schema::table('shopping_day_items', function (Blueprint $table) {
            // When the item was first priced; the sequence is the route
            $table->timestamp('purchased_at', 3)->nullable();
        });

        Schema::table('shopping_days', function (Blueprint $table) {
            // Off when prices were entered after paying: no real route
            $table->boolean('learn_order')->default(true);
            $table->timestamp('learned_at', 3)->nullable();
        });

        Schema::table('store_product', function (Blueprint $table) {
            // 0 (entrance) … 1 (end of the route); null until learned/sorted
            $table->double('score')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_product', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('shopping_days', function (Blueprint $table) {
            $table->dropColumn(['learn_order', 'learned_at']);
        });

        Schema::table('shopping_day_items', function (Blueprint $table) {
            $table->dropColumn('purchased_at');
        });
    }
};
