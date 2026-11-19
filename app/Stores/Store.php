<?php

namespace App\Stores;

use App\Models\User;
use App\Products\Product;
use App\Shopping\ShoppingDay;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A store where the owner shops. Each store has its own shopping order
 * (its layout), stored as a position per product.
 */
class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
    ];

    /**
     * @return BelongsTo<User, Store>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<ShoppingDay, Store>
     */
    public function shoppingDays(): HasMany
    {
        return $this->hasMany(ShoppingDay::class);
    }

    /**
     * @return BelongsToMany<Product, Store>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'store_product')
            ->withPivot('position', 'score');
    }

    /**
     * Position of each product in this store, keyed by product id and sorted
     * by position. Products without position are not included.
     *
     * @return array<string, int>
     */
    public function positions(): array
    {
        // Pivot rows only: no join or Product models (and their owners)
        return $this->products()->newPivotQuery()
            ->orderBy('position')
            ->pluck('position', 'product_id')
            ->map(fn ($position) => (int) $position)
            ->all();
    }

    /**
     * Replaces the store order with the given product ids, in order. A
     * manual order restarts learning: scores are spread evenly from 0 to 1.
     *
     * @param array<int, string> $productIds
     */
    public function sortProducts(array $productIds): void
    {
        $last = max(count($productIds) - 1, 1);

        $this->products()->sync(
            collect($productIds)->values()->mapWithKeys(
                fn (string $id, int $position) => [$id => [
                    'position' => $position,
                    'score' => $position / $last,
                ]]
            )
        );
    }

    protected static function newFactory(): StoreFactory
    {
        return new StoreFactory;
    }
}
