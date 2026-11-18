<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Products are now tracked only per kg or L. Prices per g/ml are tiny
     * numbers that lose precision in decimal(9, 4) (e.g. $0.0864/g), so
     * existing products in g/ml and their purchases are converted:
     * quantities are divided by 1000 and unit prices multiplied by 1000,
     * which keeps every purchase total (quantity * unit_price) unchanged.
     */
    public function up(): void
    {
        $conversions = ['g' => 'kg', 'ml' => 'L'];

        DB::transaction(function () use ($conversions) {
            foreach ($conversions as $from => $to) {
                // Query builder on purpose: includes soft deleted products.
                $productIds = DB::table('products')
                    ->where('unit', $from)
                    ->pluck('id');

                if ($productIds->isEmpty()) {
                    continue;
                }

                DB::table('shopping_day_items')
                    ->whereIn('product_id', $productIds)
                    ->update([
                        // 1000.0, not 1000: SQLite stores whole decimals as
                        // integers, so `450 / 1000` would be integer division
                        // (0) instead of 0.45.
                        'quantity' => DB::raw('quantity / 1000.0'),
                        'unit_price' => DB::raw('unit_price * 1000'),
                    ]);

                DB::table('products')
                    ->whereIn('id', $productIds)
                    ->update(['unit' => $to]);
            }
        });
    }

    /**
     * Irreversible: once converted there is no record of which kg/L products
     * were originally in g/ml.
     */
    public function down(): void
    {
        //
    }
};
