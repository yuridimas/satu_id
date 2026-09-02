<?php

namespace App\Actions\Users;

use App\Models\User;

class ToggleUser
{
    /**
     * Enable or disable the given user account.
     */
    public function toggle(User $user): User
    {
        $user->forceFill(['active' => ! $user->active])->save();

        return $user;
    }
}
