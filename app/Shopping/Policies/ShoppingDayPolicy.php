<?php

namespace App\Shopping\Policies;

use App\Models\User;
use App\Shopping\ShoppingDay;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ShoppingDayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShoppingDay $shoppingDay): Response
    {
        return $user->can('view', $shoppingDay->owner)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, User $model): Response
    {
        return $user->can('view', $model)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShoppingDay $shoppingDay): Response
    {
        return $this->view($user, $shoppingDay);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShoppingDay $shoppingDay)
    {
        return $this->update($user, $shoppingDay);
    }
}
