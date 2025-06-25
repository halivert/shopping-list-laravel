<?php

namespace App\Shopping;

use App\Models\Product;
use App\Shopping\ShoppingDay;
use Database\Factories\ShoppingDayItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingDayItem extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingDayItemFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'shopping_day_id',
        'product_id',
        'index',
        'unit_price',
        'quantity',
    ];

    protected $with = [
        'product',
    ];

    protected function casts(): array
    {
        return [
            'index' => 'integer',
            'unit_price' => 'double',
            'quantity' => 'double',
        ];
    }

    /**
     * @return BelongsTo<ShoppingDay, ShoppingDayItem>
     */
    public function shoppingDay(): BelongsTo
    {
        return $this->belongsTo(ShoppingDay::class);
    }

    /**
     * @return BelongsTo<Product, ShoppingDayItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function newFactory(): ShoppingDayItemFactory
    {
        return new ShoppingDayItemFactory;
    }
}
