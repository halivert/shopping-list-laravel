<?php

use App\Models\User;
use App\Shopping\ShoppingDay;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(
    'shopping-day-updated.{shoppingDayId}',
    function (User $user, string $shoppingDayId) {
        return $user->can(
            'view',
            ShoppingDay::query()->find($shoppingDayId)
        );
    }
);

Broadcast::channel(
    'product-list.{ownerId}',
    function (User $user, string $ownerId) {
        $owner = User::query()->find($ownerId);

        return $owner && $user->can('view', $owner);
    }
);
