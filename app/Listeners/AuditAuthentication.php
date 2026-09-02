<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditAuthentication
{
    /**
     * Record authentication related events to the audit log.
     */
    public function handle(Login|Failed|Logout $event): void
    {
        $user = $this->resolveUser($event);

        if (! $user instanceof User) {
            logger()->warning('Authentication audit skipped for unknown account.', [
                'event' => $event::class,
                'credentials' => $event->credentials ?? [],
            ]);

            return;
        }

        AuditLogger::record(
            auditable: $user,
            event: match (true) {
                $event instanceof Login => 'login',
                $event instanceof Failed => 'failed',
                $event instanceof Logout => 'logout',
            },
            actor: $event instanceof Logout ? $user : null,
            tags: 'authentication',
        );
    }

    /**
     * Resolve the user related to the event.
     */
    protected function resolveUser(Login|Failed|Logout $event): ?User
    {
        if ($event instanceof Login || $event instanceof Logout) {
            if ($event->user instanceof User) {
                return $event->user;
            }

            return User::query()->whereKey($event->user->getAuthIdentifier())->first();
        }

        $email = $event->credentials['email'] ?? null;

        if (is_string($email)) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->first();
        }

        return null;
    }
}
