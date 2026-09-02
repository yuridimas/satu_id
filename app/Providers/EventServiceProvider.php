<?php

namespace App\Providers;

use App\Listeners\AuditAuthentication;
use App\Listeners\BlockDisabledUsers;
use App\Listeners\RejectDisabledUsersOnAttempt;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            AuditAuthentication::class,
            BlockDisabledUsers::class,
        ],
        Failed::class => [
            AuditAuthentication::class,
        ],
        Logout::class => [
            AuditAuthentication::class,
        ],
        Attempting::class => [
            RejectDisabledUsersOnAttempt::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
