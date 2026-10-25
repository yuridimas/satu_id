<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SatuID - {{ __('One account for all applications') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css'])
        @fluxAppearance
        @include('partials.appearance-default')
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- Navbar --}}
        <header class="sticky top-0 z-10 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <flux:icon.key class="size-5" />
                    </span>
                    <span class="text-lg font-bold tracking-tight">SatuID</span>
                </a>
                <nav class="hidden items-center gap-6 text-sm text-zinc-600 md:flex dark:text-zinc-400">
                    <a href="#fitur" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Features') }}</a>
                    <a href="#alur" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('How it works') }}</a>
                    <a href="#teknologi" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Technology') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                {{ __('Log in') }}
                            </a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                                {{ __('Register') }}
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-24">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                <flux:icon.shield-check class="size-3.5" />
                {{ __('SSO • OAuth2 • Portfolio showcase') }}
            </span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                {{ __('One account for all applications') }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-zinc-600 sm:text-lg dark:text-zinc-400">
                {{ __('SatuID is a central identity provider: register and sign in once, then access multiple organization portals without signing in again. It includes OAuth2, 2FA, passkeys, and audit logs.') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-indigo-500">
                        {{ __('Open dashboard') }}
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-indigo-500">
                            {{ __('Create a free account') }}
                        </a>
                    @endif
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-xl border border-zinc-300 px-6 py-3 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('Sign in to your account') }}
                        </a>
                    @endif
                @endauth
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-center text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Why SatuID?') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-center text-sm text-zinc-600 dark:text-zinc-400">{{ __('Centralized authentication and authorization with modern standards.') }}</p>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            <flux:icon.key class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Single sign-on (SSO)') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('One session for all connected applications through OAuth2 Authorization Code and Client Credentials.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                            <flux:icon.shield-check class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Layered security') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Two-factor authentication, WebAuthn passkeys, and automatic blocking of inactive accounts.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                            <flux:icon.clock class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Complete audit log') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Every change to users, clients, and sign-ins is recorded and traceable by superusers.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400">
                            <flux:icon.users class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Centralized management') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Manage users, OAuth clients, active tokens, and access revocation from one admin dashboard.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            <flux:icon.document-chart-bar class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Monitoring and exports') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Activity charts, Laravel Pulse, and queued XLSX data exports.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                            <flux:icon.globe-alt class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Two languages') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Switch between Indonesian and English interfaces anytime in settings.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Alur --}}
        <section id="alur" class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-center text-2xl font-bold tracking-tight sm:text-3xl">{{ __('User flow') }}</h2>
                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">1</span>
                        <h3 class="mt-4 font-semibold">{{ __('Create an account') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Create a SatuID account with your email address and password.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">2</span>
                        <h3 class="mt-4 font-semibold">{{ __('Sign in once') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Sign in with 2FA or a passkey for added security.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">3</span>
                        <h3 class="mt-4 font-semibold">{{ __('Access all portals') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Move between applications without signing in again, and manage permissions in one place.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Teknologi --}}
        <section id="teknologi" class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Built with') }}</h2>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
                    @foreach (['Laravel 13', 'Livewire 4', 'Flux UI', 'Tailwind v4', 'Fortify', 'Passport OAuth2', 'PostgreSQL', 'Pest'] as $tech)
                        <span class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-1.5 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">{{ $tech }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-center sm:flex-row sm:px-6 sm:text-left">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <flux:icon.key class="size-4" />
                    </span>
                    <span class="text-sm font-semibold">SatuID</span>
                </div>
                <p class="max-w-md text-xs text-zinc-500 dark:text-zinc-500">
                    {{ __('Educational project showcase — not an official government production system.') }}
                    <a href="{{ route('login') }}" class="font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Log in') }}</a>
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
