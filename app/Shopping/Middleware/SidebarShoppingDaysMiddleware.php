<?php

namespace App\Shopping\Middleware;

use App\Models\ShoppingDay;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class SidebarShoppingDaysMiddleware extends Middleware
{
    public function share(Request $request): array
    {
        /** @var User|null */
        $user = $request->user();

        /** @var ShoppingDay|null */
        $shoppingDay = $request->route('shoppingDay');

        /** @var User|null */
        $owner = $request->route('owner', $shoppingDay?->owner);

        if (!$user or !$owner) return parent::share($request);

        if ($user->is($owner)) {
            return parent::share($request);
        }

        $lastFiveShoppingDays = fn() => $owner->shoppingDays()
            ->limit(5)->get();

        return [
            ...parent::share($request),
            'sidebarShoppingDays' => $lastFiveShoppingDays,
        ];
    }
}
