<?php

namespace App\Shopping;

use App\Products\Product;
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
        'purchased_at',
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
            'purchased_at' => 'datetime',
        ];
    }

    /**
     * Records when the item was first priced: the sequence of purchases is
     * the route through the store. Correcting a price keeps the time;
     * removing it (null or 0, an unchecked item in the list) means the item
     * was not bought.
     */
    protected static function booted(): void
    {
        static::saving(function (ShoppingDayItem $item) {
            if (! $item->isDirty('unit_price')) {
                return;
            }

            if ($item->unit_price > 0) {
                $item->purchased_at ??= now();
            } else {
                $item->purchased_at = null;
            }
        });
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
        return $this->belongsTo(Product::class)->withTrashed();
    }

    protected static function newFactory(): ShoppingDayItemFactory
    {
        return new ShoppingDayItemFactory;
    }
}
