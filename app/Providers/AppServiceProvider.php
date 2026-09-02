<?php

namespace App\Providers;

use App\Models\OAuthClient;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureDefaults();
        $this->configurePassport();
    }

    /**
     * Configure authorization gates and policies.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('view-admin', fn (User $user): bool => $user->isSuperuser());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(OAuthClient::class, ClientPolicy::class);
    }

    /**
     * Configure Passport based services.
     */
    protected function configurePassport(): void
    {
        Passport::useClientModel(OAuthClient::class);

        Passport::tokensExpireIn(now()->addMinutes(config('passport.tokens_expire_in')));
        Passport::refreshTokensExpireIn(now()->addMinutes(config('passport.refresh_tokens_expire_in')));
        Passport::clientCredentialsTokensExpireIn(now()->addMinutes(config('passport.client_credentials_expire_in')));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
