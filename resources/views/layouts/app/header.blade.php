<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-ambient-canvas font-sans text-zinc-900 antialiased dark:text-zinc-100">
        <flux:header container class="sticky top-0 z-10 border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px hidden lg:flex">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="shield-check" :href="route('user.authorized-apps')" :current="request()->routeIs('user.authorized-apps')" wire:navigate>
                    {{ __('Authorized apps') }}
                </flux:navbar.item>

                @if (auth()->user()->isSuperuser())
                    <flux:navbar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        {{ __('Users') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="key" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*')" wire:navigate>
                        {{ __('OAuth2 Clients') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="clock" :href="route('admin.audits.index')" :current="request()->routeIs('admin.audits.*')" wire:navigate>
                        {{ __('Audit Logs') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="presentation-chart-line" :href="route('pulse')" target="_blank">
                        {{ __('Monitoring') }}
                    </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <div class="flex items-center gap-3">
                <flux:badge color="zinc">{{ auth()->user()->isSuperuser() ? __('Superuser') : __('User') }}</flux:badge>
                <x-desktop-user-menu />
            </div>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('user.authorized-apps')" :current="request()->routeIs('user.authorized-apps')" wire:navigate>
                        {{ __('Authorized apps') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if (auth()->user()->isSuperuser())
                    <flux:sidebar.group :heading="__('Administration')">
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="key" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*')" wire:navigate>
                            {{ __('OAuth2 Clients') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('admin.audits.index')" :current="request()->routeIs('admin.audits.*')" wire:navigate>
                            {{ __('Audit Logs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="presentation-chart-line" :href="route('pulse')" target="_blank">
                            {{ __('Monitoring') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>
        </flux:sidebar>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>