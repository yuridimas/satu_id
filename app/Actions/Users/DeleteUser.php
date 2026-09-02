<?php

namespace App\Actions\Users;

use App\Models\User;

class DeleteUser
{
    /**
     * Soft delete the given user account and revoke its access tokens.
     */
    public function delete(User $user): void
    {
        $user->tokens()->get()->each(function ($token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        $user->delete();
    }
}
