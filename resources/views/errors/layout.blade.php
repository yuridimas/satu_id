<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => trim($__env->yieldContent('title'))])
        <script id="theme-default">
            try {
                if (window.localStorage.getItem('flux.appearance') === null) {
                    window.Flux.applyAppearance('light');
                }
            } catch (error) {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <main class="flex min-h-svh flex-col items-center justify-center gap-10 px-6 py-12 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-purple-600 dark:focus-visible:outline-purple-400">
                <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                    <flux:icon.key class="size-5" />
                </span>
                <span class="text-lg font-bold tracking-tight">SatuID</span>
            </a>

            <div class="w-full max-w-lg text-center">
                <p class="text-7xl font-semibold tracking-tight text-zinc-500 sm:text-8xl dark:text-zinc-400">@yield('code')</p>
                <h1 class="mt-6 text-2xl font-semibold tracking-tight sm:text-3xl">@yield('message')</h1>

                @hasSection('description')
                    <p class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-400">@yield('description')</p>
                @endif

                <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <flux:button :href="route('home')" variant="primary" color="purple">
                        {{ __('Go Home') }}
                    </flux:button>
                    @yield('action')
                </div>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
