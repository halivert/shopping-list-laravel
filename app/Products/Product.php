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

    /**
     * Returns purchase history sorted oldest-first, each entry carrying
     * the shopping day id, date string, quantity, and unit price.
     *
     * @return array<int, array{shoppingDayId: string, date: string, quantity: float|null, unitPrice: float|null}>
     */
    public function getPurchaseHistory(): array
    {
        return $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->shoppingDay !== null)
            ->sortBy(fn(ShoppingDayItem $item) => $item->shoppingDay->date)
            ->values()
            ->map(fn(ShoppingDayItem $item) => [
                'shoppingDayId' => $item->shopping_day_id,
                'date'          => $item->shoppingDay->date->toDateString(),
                'quantity'      => $item->quantity,
                'unitPrice'     => $item->unit_price,
            ])
            ->all();
    }

    public function getTimesBought(): int
    {
        return $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->shoppingDay !== null)
            ->count();
    }

    public function getAverageUnitPrice(): float | null
    {
        $prices = $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->unit_price !== null)
            ->pluck('unit_price');

        return $prices->isEmpty() ? null : round($prices->avg(), 4);
    }

    public function getMinUnitPrice(): float | null
    {
        $prices = $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->unit_price !== null)
            ->pluck('unit_price');

        return $prices->isEmpty() ? null : $prices->min();
    }

    public function getMaxUnitPrice(): float | null
    {
        $prices = $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->unit_price !== null)
            ->pluck('unit_price');

        return $prices->isEmpty() ? null : $prices->max();
    }

    public function getAverageQuantity(): float | null
    {
        $quantities = $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->quantity !== null)
            ->pluck('quantity');

        return $quantities->isEmpty() ? null : round($quantities->avg(), 4);
    }

    /**
     * Average number of days between consecutive purchases. Null when fewer
     * than two purchase records exist.
     */
    public function getAverageDaysBetweenPurchases(): float | null
    {
        $dates = $this->shoppingDayItems
            ->filter(fn(ShoppingDayItem $item) => $item->shoppingDay !== null)
            ->sortBy(fn(ShoppingDayItem $item) => $item->shoppingDay->date)
            ->map(fn(ShoppingDayItem $item) => $item->shoppingDay->date)
            ->values();

        if ($dates->count() < 2) {
            return null;
        }

        $gaps = collect();
        for ($i = 1; $i < $dates->count(); $i++) {
            $gaps->push($dates[$i - 1]->diffInDays($dates[$i]));
        }

        return round($gaps->avg(), 4);
    }

    /**
     * How many days one unit of this product lasts on average.
     * = avg days between purchases / avg quantity.
     */
    public function getDaysPerUnit(): float | null
    {
        $avgDays = $this->getAverageDaysBetweenPurchases();
        $avgQty  = $this->getAverageQuantity();

        if ($avgDays === null || $avgQty === null || $avgQty == 0) {
            return null;
        }

        return round($avgDays / $avgQty, 4);
    }

    /**
     * Estimated how long one purchase (at required_quantity) will last.
     * = days per unit * required_quantity.
     */
    public function getEstimatedDuration(): float | null
    {
        $daysPerUnit = $this->getDaysPerUnit();

        if ($daysPerUnit === null) {
            return null;
        }

        return round($daysPerUnit * $this->required_quantity, 4);
    }

    public function getTotalSpent(): float
    {
        return round(
            $this->shoppingDayItems
                ->filter(fn(ShoppingDayItem $item) => $item->unit_price !== null && $item->quantity !== null)
                ->sum(fn(ShoppingDayItem $item) => $item->unit_price * $item->quantity),
            4
        );
    }
}
