<?php

namespace App\Policies;

use App\Models\OAuthClient;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view the client management pages.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can create OAuth clients.
     */
    public function create(User $user): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can view the given client.
     */
    public function view(User $user, OAuthClient $client): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can update the given client.
     */
    public function update(User $user, OAuthClient $client): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can rotate the given client secret.
     */
    public function rotate(User $user, OAuthClient $client): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can toggle the given client status.
     */
    public function toggle(User $user, OAuthClient $client): bool
    {
        return $user->isSuperuser();
    }

    /**
     * Determine whether the user can delete the given client.
     */
    public function delete(User $user, OAuthClient $client): bool
    {
        return $user->isSuperuser();
    }
}
