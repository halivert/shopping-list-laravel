<?php

namespace App\Shopping;

use App\Models\User;
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
        'date',
    ];

    protected $with = [
        'owner',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime:Y-m-d',
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
     * @return HasMany<ShoppingDayItem, ShoppingDay>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingDayItem::class);
    }

    protected static function newFactory(): ShoppingDayFactory
    {
        return new ShoppingDayFactory;
    }
}
