<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SatuID - {{ __('Satu Akun untuk Semua Aplikasi') }}</title>

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
                    <a href="#fitur" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Fitur') }}</a>
                    <a href="#alur" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Alur') }}</a>
                    <a href="#teknologi" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Teknologi') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                {{ __('Masuk') }}
                            </a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
                                {{ __('Daftar') }}
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
                {{ __('SSO • OAuth2 • Showcase Portofolio') }}
            </span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                {{ __('Satu Akun untuk Semua Aplikasi') }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-zinc-600 sm:text-lg dark:text-zinc-400">
                {{ __('SatuID adalah identity provider terpusat: daftar dan masuk sekali, lalu akses berbagai portal milik instansi tanpa login berulang. Dilengkapi OAuth2, 2FA, passkey, dan audit log.') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-indigo-500">
                        {{ __('Buka Dashboard') }}
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-indigo-500">
                            {{ __('Buat Akun Gratis') }}
                        </a>
                    @endif
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-xl border border-zinc-300 px-6 py-3 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('Masuk ke Akun') }}
                        </a>
                    @endif
                @endauth
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-center text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Kenapa SatuID?') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-center text-sm text-zinc-600 dark:text-zinc-400">{{ __('Autentikasi dan otorisasi terpusat dengan standar modern.') }}</p>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            <flux:icon.key class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Login Sekali (SSO)') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Satu sesi untuk semua aplikasi terhubung via OAuth2 Authorization Code & Client Credentials.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                            <flux:icon.shield-check class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Keamanan Berlapis') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Two-factor authentication, passkey WebAuthn, dan pemblokiran akun nonaktif otomatis.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                            <flux:icon.clock class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Audit Log Lengkap') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Setiap perubahan user, client, dan login tercatat dan bisa ditelusuri superuser.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400">
                            <flux:icon.users class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Manajemen Terpusat') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kelola user, OAuth client, token aktif, dan revoke akses dari satu dasbor admin.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            <flux:icon.document-chart-bar class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Monitoring & Export') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Grafik aktivitas, Laravel Pulse, dan export data XLSX via antrean.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                            <flux:icon.globe-alt class="size-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ __('Dua Bahasa') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Antarmuka Indonesia dan Inggris yang bisa diganti kapan saja dari pengaturan.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Alur --}}
        <section id="alur" class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-center text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Alur Pakai') }}</h2>
                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">1</span>
                        <h3 class="mt-4 font-semibold">{{ __('Daftar Akun') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Buat satu akun SatuID dengan email dan kata sandi.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">2</span>
                        <h3 class="mt-4 font-semibold">{{ __('Masuk Sekali') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Login dengan 2FA atau passkey untuk keamanan ekstra.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">3</span>
                        <h3 class="mt-4 font-semibold">{{ __('Akses Semua Portal') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Pindah antar aplikasi tanpa login ulang, kelola izin dari satu tempat.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Teknologi --}}
        <section id="teknologi" class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Dibangun Dengan') }}</h2>
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
                    {{ __('Project showcase pembelajaran — bukan sistem produksi instansi resmi.') }}
                    <a href="{{ route('login') }}" class="font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Masuk') }}</a>
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
