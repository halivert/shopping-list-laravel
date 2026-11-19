<?php

namespace App\Stores;

use App\Models\User;
use App\Products\Product;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * @return BelongsToMany<Product, Store>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'store_product')
            ->withPivot('position');
    }

    /**
     * Position of each product in this store, keyed by product id and sorted
     * by position. Products without position are not included.
     *
     * @return array<string, int>
     */
    public function positions(): array
    {
        return $this->products()->newPivotStatement()
            ->where('store_id', $this->id)
            ->orderBy('position')
            ->pluck('position', 'product_id')
            ->map(fn ($position) => (int) $position)
            ->all();
    }

    /**
     * Replaces the store order with the given product ids, in order.
     *
     * @param array<int, string> $productIds
     */
    public function sortProducts(array $productIds): void
    {
        $this->products()->sync(
            collect($productIds)->values()->mapWithKeys(
                fn (string $id, int $position) => [$id => ['position' => $position]]
            )
        );
    }

    protected static function newFactory(): StoreFactory
    {
        return new StoreFactory;
    }
}
