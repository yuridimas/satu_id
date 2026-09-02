<?php

namespace App\Actions\Users;

use App\Models\User;

class RestoreUser
{
    /**
     * Restore the given soft deleted user account.
     */
    public function restore(User $user): User
    {
        $user->restore();

        return $user;
    }
}
