<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Access extends Pivot
{
    protected $fillable = [];

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
