<?php

namespace App\Policies;

use App\Models\Access;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        Log::info('User trying to access everyone', [
            'user' => $user->id,
            'userName' => $user->name
        ]);

        return $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): Response
    {
        if ($user->is($model)) {
            return $this->allow();
        }

        $access = Access::query()
            ->where('user_id', $user->id)
            ->whereMorphedTo('accessible', $model)
            ->first();

        return $access ? $this->allow() : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        Log::info('User trying to create someone', [
            'user' => $user->id,
            'userName' => $user->name
        ]);

        return $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): Response
    {
        return $user->is($model) ? $this->allow() : $this->denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): Response
    {
        return $this->update($user, $model);
    }
}
