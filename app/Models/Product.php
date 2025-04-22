<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasUuids;


    protected $fillable = [
        'owner_id',
        'name',
    ];

    protected $with = [
        'owner',
    ];

    /**
     * @return BelongsTo<User, Product>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
