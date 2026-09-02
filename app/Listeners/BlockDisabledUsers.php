<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class BlockDisabledUsers
{
    /**
     * Prevent disabled accounts from signing in.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->isActive()) {
            return;
        }

        auth($event->guard)->logout();

        throw ValidationException::withMessages([
            Fortify::username() => __('This account has been disabled.'),
        ]);
    }
}
