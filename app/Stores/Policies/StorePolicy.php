<?php

namespace App\Stores\Policies;

use App\Models\User;
use App\Stores\Store;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * Stores are shared like the products: whoever can see the owner's products
 * can manage their stores.
 */
class StorePolicy
{
    use HandlesAuthorization;

    public function create(User $user, User $owner): Response
    {
        return $user->can('view', $owner)
            ? $this->allow()
            : $this->denyAsNotFound();
    }

    public function view(User $user, Store $store): Response
    {
        return $this->create($user, $store->owner);
    }

    public function update(User $user, Store $store): Response
    {
        return $this->view($user, $store);
    }

    public function delete(User $user, Store $store): Response
    {
        return $this->update($user, $store);
    }
}
