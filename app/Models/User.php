<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
    ];

    /**
     * @return HasMany<Product, User>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'owner_id');
    }

    /**
     * @return MorphMany<Access, User>
     */
    public function sharedWith(): MorphMany
    {
        return $this->morphMany(Access::class, 'accessible');
    }

    /**
     * @return HasMany<Access, User>
     */
    public function sharedBy(): HasMany
    {
        return $this->hasMany(Access::class, 'user_id');
    }
}
