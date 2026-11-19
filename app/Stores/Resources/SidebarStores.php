<?php

namespace App\Stores\Resources;

use App\Http\Resources\UserResource;
use App\Models\User;
use Closure;

/**
 * Stores listed in the sidebar, with the owner whose sort page they link
 * to. Shared lazily, like the sidebar shopping days.
 */
class SidebarStores
{
    public static function for(User $owner): Closure
    {
        return fn () => [
            'owner' => UserResource::make($owner),
            'stores' => StoreResource::collection($owner->stores),
        ];
    }
}
