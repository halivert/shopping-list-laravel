<?php

namespace App\Shopping;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(User::class);
    }
}
