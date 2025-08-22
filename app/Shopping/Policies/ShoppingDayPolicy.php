<?php

namespace App\Shopping\Policies;

use App\Models\User;
use App\Shopping\ShoppingDay;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class ShoppingDayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, User $owner): Response
    {
        return $user->can('view', $owner)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

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
    public function create(User $user, User $owner): Response
    {
        return $user->can('view', $owner)
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
    public function delete(User $user, ShoppingDay $shoppingDay): Response
    {
        return $this->update($user, $shoppingDay);
    }
}
