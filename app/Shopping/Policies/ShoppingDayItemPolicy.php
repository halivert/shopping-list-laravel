<?php

namespace App\Shopping\Policies;

use App\Models\User;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ShoppingDayItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ShoppingDay $shoppingDay): Response
    {
        return $user->can('update', $shoppingDay)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(
        User $user,
        ShoppingDayItem $shoppingDayItem
    ): Response {
        return $this->create($user, $shoppingDayItem->shoppingDay);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(
        User $user,
        ShoppingDayItem $shoppingDayItem
    ): Response {
        return $this->create($user, $shoppingDayItem->shoppingDay);
    }
}
