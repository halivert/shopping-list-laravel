<?php

namespace App\Shopping;

use App\Models\User;
use App\Products\Product;
use App\Stores\Store;
use Database\Factories\ShoppingDayFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingDay extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingDayFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'store_id',
        'date',
        'learn_order',
        'learned_at',
    ];

    protected $with = [
        'owner',
        'store',
    ];

    /**
     * Memoized positions of the day's store, see itemIndexFor().
     *
     * @var array<string, int>|null
     */
    private ?array $storePositions = null;

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'learn_order' => 'boolean',
            'learned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, ShoppingDay>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Store where this day's shopping is done; its order sorts the items.
     *
     * @return BelongsTo<Store, ShoppingDay>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return HasMany<ShoppingDayItem, ShoppingDay>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingDayItem::class, 'shopping_day_id')
            ->orderBy('index');
    }

    /**
     * Index for a new item of $product: its position in the day's store or,
     * for days without store, its shopping_index. Null when it has none.
     */
    public function itemIndexFor(Product $product): ?int
    {
        if (! $this->store) {
            return $product->shopping_index;
        }

        $this->storePositions ??= $this->store->positions();

        return $this->storePositions[$product->id] ?? null;
    }

    /**
     * Index after every product sorted in the day's store, for products
     * without position. Days without store keep the legacy 0.
     */
    public function unsortedItemIndex(): int
    {
        if (! $this->store) {
            return 0;
        }

        $this->storePositions ??= $this->store->positions();

        return $this->storePositions ? max($this->storePositions) + 1 : 0;
    }

    protected static function newFactory(): ShoppingDayFactory
    {
        return new ShoppingDayFactory;
    }
}
