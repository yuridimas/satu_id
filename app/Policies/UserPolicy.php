<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the user management pages.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can create user accounts.
     */
    public function create(User $user): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can view the given user account.
     */
    public function view(User $user, User $model): bool
    {
        return $this->manageable($user, $model);
    }

    /**
     * Determine whether the user can update the given user account.
     */
    public function update(User $user, User $model): bool
    {
        return $this->manageable($user, $model);
    }

    /**
     * Determine whether the user can disable or enable the given user account.
     */
    public function disable(User $user, User $model): bool
    {
        return $this->manageable($user, $model) && $user->getKey() !== $model->getKey();
    }

    /**
     * Determine whether the user can soft delete the given user account.
     */
    public function delete(User $user, User $model): bool
    {
        return $this->manageable($user, $model);
    }

    /**
     * Determine whether the user can view the trash of user accounts.
     */
    public function restoreInTrash(User $user): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can restore the given user account.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->manageable($user, $model);
    }

    /**
     * Only superusers may manage non-superuser accounts.
     */
    protected function manageable(User $user, User $model): bool
    {
        return $user->isSuperuser() && ! $model->isSuperuser();
    }
}
