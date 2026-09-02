<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class RejectDisabledUsersOnAttempt
{
    /**
     * Reject credentials of disabled accounts before they are verified.
     */
    public function handle(Attempting $event): void
    {
        $email = $event->credentials['email'] ?? null;

        if (! is_string($email)) {
            return;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->withTrashed()
            ->first();

        if (! $user instanceof User || $user->isActive()) {
            return;
        }

        throw ValidationException::withMessages([
            Fortify::username() => __('This account has been disabled.'),
        ]);
    }
}
