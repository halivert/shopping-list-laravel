<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The shopping order used to be products.shopping_index. Every user with
     * products (and no store yet) gets an initial store whose order is copied
     * from it, so the current order is not lost.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Query builder on purpose: includes soft deleted products.
            $ownerIds = DB::table('products')
                ->whereNotIn('owner_id', DB::table('stores')->select('owner_id'))
                ->distinct()
                ->pluck('owner_id');

            $now = now();

            foreach ($ownerIds as $ownerId) {
                $storeId = (string) Str::uuid();

                DB::table('stores')->insert([
                    'id' => $storeId,
                    'owner_id' => $ownerId,
                    'name' => 'Mi tienda',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $positions = DB::table('products')
                    ->where('owner_id', $ownerId)
                    ->whereNotNull('shopping_index')
                    ->get(['id', 'shopping_index'])
                    ->map(fn ($product) => [
                        'store_id' => $storeId,
                        'product_id' => $product->id,
                        'position' => $product->shopping_index,
                    ]);

                foreach ($positions->chunk(500) as $chunk) {
                    DB::table('store_product')->insert($chunk->all());
                }
            }
        });
    }

    /**
     * products.shopping_index is kept, so nothing is lost by leaving the
     * stores in place.
     */
    public function down(): void
    {
        //
    }
};
