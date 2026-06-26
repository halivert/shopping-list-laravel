<?php

namespace App\Products;

use App\Models\User;
use App\Shopping\ShoppingDayItem;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'search_index',
        'shopping_index',
        'is_required',
        'required_quantity',
    ];

    protected $with = [
        'owner',
    ];

    protected function casts(): array
    {
        return [
            'search_index' => 'integer',
            'shopping_index' => 'integer',
            'is_required' => 'boolean',
            'required_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ShoppingDayItem, Product>
     */
    public function shoppingDayItems(): HasMany
    {
        return $this->hasMany(ShoppingDayItem::class);
    }

    protected static function newFactory(): ProductFactory
    {
        return new ProductFactory;
    }

    public function getLastPrice(?Carbon $date = null): float | null
    {
        $date ??= now();

        return $this->shoppingDayItems->filter(
            function (ShoppingDayItem $shoppingDayItem) {
                return $shoppingDayItem->unit_price;
            }
        )->filter(
            fn(ShoppingDayItem $shoppingDayItem) => $shoppingDayItem
                ->shoppingDay->date->lessThan($date)
        )->sortByDesc(function (ShoppingDayItem $shoppingDayItem) {
            return $shoppingDayItem->shoppingDay->date;
        })->first()?->unit_price;
    }
}
