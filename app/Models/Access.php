<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Access extends Model
{
    use HasUuids;

    protected $table = 'access';

    protected $fillable = [
        'user_email'
    ];

    protected $with = [
        'user',
        'accessible',
    ];

    /**
     * @return BelongsTo<User, Access>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, Access>
     */
    public function accessible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, Access>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
