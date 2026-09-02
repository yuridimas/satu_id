<?php

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Token;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * Compact KPI stats for superuser.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function kpi(): array
    {
        return [
            'totalUsers' => User::where('role', UserRole::User)->count(),
            'activeUsers' => User::where('role', UserRole::User)->where('active', true)->count(),
            'trashUsers' => User::onlyTrashed()->where('role', UserRole::User)->count(),
            'totalClients' => OAuthClient::count(),
            'activeClients' => OAuthClient::where('revoked', false)->count(),
            'activeTokens' => Token::where('revoked', false)->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'todayAudits' => Audit::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Weekly activity data for chart (7 days, grouped by User vs OAuth).
     *
     * @return array{labels: list<string>, user: list<int>, oauth: list<int>}
     */
    #[Computed]
    public function weeklyActivity(): array
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(now()->subDays($i)->startOfDay());
        }

        $audits = Audit::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->whereIn('auditable_type', [User::class, OAuthClient::class])
            ->get(['auditable_type', 'created_at']);

        $userCounts = array_fill(0, 7, 0);
        $oauthCounts = array_fill(0, 7, 0);

        foreach ($audits as $audit) {
            $dayIndex = $days->search(fn ($d) => $audit->created_at->startOfDay()->eq($d));
            if ($dayIndex === false) {
                continue;
            }
            if ($audit->auditable_type === User::class) {
                $userCounts[$dayIndex]++;
            } elseif ($audit->auditable_type === OAuthClient::class) {
                $oauthCounts[$dayIndex]++;
            }
        }

        $labels = $days->map(fn ($d) => $d->translatedFormat('D'))->toArray();

        return [
            'labels' => $labels,
            'user' => $userCounts,
            'oauth' => $oauthCounts,
        ];
    }

    /**
     * 10 most recent audits.
     */
    #[Computed]
    public function recentAudits()
    {
        return Audit::with('user')
            ->latest('created_at')
            ->take(10)
            ->get();
    }

    /**
     * Health & operational status.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function health(): array
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $queueSize = 0;
        try {
            $queueSize = Queue::size();
        } catch (\Throwable) {
            // Queue driver may not support size()
        }

        return [
            'db' => $dbOk ? 'connected' : 'disconnected',
            'queue' => config('queue.default'),
            'queueSize' => $queueSize,
            'failedJobs' => DB::table('failed_jobs')->count(),
            'passportClients' => OAuthClient::count(),
        ];
    }

    /**
     * Build the detail URL for an audit's auditable model.
     */
    public function auditUrl(Audit $audit): ?string
    {
        if (! $audit->auditable_type || ! $audit->auditable_id) {
            return null;
        }

        return match ($audit->auditable_type) {
            'App\Models\User' => route('admin.users.show', $audit->auditable_id),
            'App\Models\OAuthClient' => route('admin.clients.show', $audit->auditable_id),
            default => null,
        };
    }

}; ?>

<section class="w-full">
    @if (auth()->user()->isSuperuser())
        {{-- Header --}}
        <div>
            <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Ringkasan SSO — status, aktivitas, dan kesehatan sistem') }}</flux:subheading>
        </div>

        {{-- Row 1: Health Card --}}
        <flux:card class="mt-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <flux:heading size="sm">{{ __('Kesehatan Sistem') }}</flux:heading>
                <div class="flex flex-wrap items-center gap-5 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('DB') }}</span>
                        <flux:badge :color="$this->health['db']==='connected' ? 'green' : 'rose'" size="sm">{{ $this->health['db'] }}</flux:badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Queue') }}</span>
                        <span class="font-mono text-xs">{{ $this->health['queue'] }}</span>
                        @if ($this->health['queueSize'] > 0)
                            <flux:badge color="orange" size="sm">{{ $this->health['queueSize'] }}</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">0</flux:badge>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Failed Jobs') }}</span>
                        @if ($this->health['failedJobs'] > 0)
                            <flux:badge color="rose" size="sm">{{ $this->health['failedJobs'] }}</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">0</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Row 2: 4 Stats Cards --}}
        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon.users class="size-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</div>
                    <div class="text-lg font-semibold leading-tight">{{ $this->kpi['totalUsers'] }}</div>
                    <div class="text-[11px] text-zinc-400">
                        <span class="text-green-600 dark:text-green-400">{{ $this->kpi['activeUsers'] }}</span> {{ __('aktif') }}
                        @if ($this->kpi['trashUsers'] > 0)
                            · <span class="text-orange-600 dark:text-orange-400">{{ $this->kpi['trashUsers'] }}</span> {{ __('trash') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon.key class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Clients') }}</div>
                    <div class="text-lg font-semibold leading-tight">{{ $this->kpi['totalClients'] }}</div>
                    <div class="text-[11px] text-zinc-400">
                        <span class="text-green-600 dark:text-green-400">{{ $this->kpi['activeClients'] }}</span> {{ __('aktif') }}
                        @if ($this->kpi['totalClients'] - $this->kpi['activeClients'] > 0)
                            · <span class="text-zinc-500">{{ $this->kpi['totalClients'] - $this->kpi['activeClients'] }}</span> {{ __('revoked') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                    <flux:icon.shield-check class="size-5 text-sky-600 dark:text-sky-400" />
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Tokens') }}</div>
                    <div class="text-lg font-semibold leading-tight">{{ $this->kpi['activeTokens'] }}</div>
                    <div class="text-[11px] text-green-600 dark:text-green-400">{{ __('aktif') }}</div>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.clock class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Audit Hari Ini') }}</div>
                    <div class="text-lg font-semibold leading-tight">{{ $this->kpi['todayAudits'] }}</div>
                    <div class="text-[11px] text-zinc-400">{{ __('event') }}</div>
                </div>
            </div>
        </div>

        {{-- Row 3: Perlu Perhatian (conditional, no failed jobs) --}}
        @php
            $hasAttention = $this->kpi['trashUsers'] > 0
                || ($this->kpi['totalClients'] - $this->kpi['activeClients']) > 0;
        @endphp
        @if ($hasAttention)
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($this->kpi['trashUsers'] > 0)
                    <a href="{{ route('admin.users.trash') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-sm transition hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-950 dark:hover:bg-orange-900">
                        <flux:icon.exclamation-triangle class="size-4 text-orange-500" />
                        <span class="font-medium text-orange-800 dark:text-orange-200">{{ $this->kpi['trashUsers'] }} {{ __('user di trash') }}</span>
                        <flux:icon.arrow-right class="size-3 text-orange-400" />
                    </a>
                @endif

                @if (($this->kpi['totalClients'] - $this->kpi['activeClients']) > 0)
                    <a href="{{ route('admin.clients.index') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-sm transition hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-950 dark:hover:bg-orange-900">
                        <flux:icon.exclamation-triangle class="size-4 text-orange-500" />
                        <span class="font-medium text-orange-800 dark:text-orange-200">{{ $this->kpi['totalClients'] - $this->kpi['activeClients'] }} {{ __('client revoked') }}</span>
                        <flux:icon.arrow-right class="size-3 text-orange-400" />
                    </a>
                @endif
            </div>
        @endif

        {{-- Row 4: Chart Aktivitas Terbaru (Chart.js) --}}
        @php $chart = $this->weeklyActivity(); @endphp
        <flux:card class="mt-6">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Aktivitas Terbaru') }}</flux:heading>
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="block size-2.5 rounded-full bg-indigo-500"></span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('User') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="block size-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('OAuth') }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-4" style="height: 220px">
                <canvas id="activityChart"></canvas>
            </div>
        </flux:card>

        @once
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const ctx = document.getElementById('activityChart');
                    if (!ctx) return;

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($chart['labels']),
                            datasets: [
                                {
                                    label: 'User',
                                    data: @json($chart['user']),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99,102,241,0.1)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#6366f1',
                                    tension: 0,
                                    fill: true,
                                },
                                {
                                    label: 'OAuth',
                                    data: @json($chart['oauth']),
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16,185,129,0.1)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#10b981',
                                    tension: 0,
                                    fill: true,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#18181b',
                                    titleColor: '#fafafa',
                                    bodyColor: '#d4d4d8',
                                    padding: 10,
                                    cornerRadius: 8,
                                },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#a1a1aa', font: { size: 11 } },
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(212,212,216,0.15)' },
                                    ticks: {
                                        color: '#a1a1aa',
                                        font: { size: 11 },
                                        stepSize: 1,
                                        precision: 0,
                                    },
                                },
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                        },
                    });
                });
            </script>
        @endonce

        {{-- Row 5: Audit Timeline --}}
        <div class="mt-6">
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Audit Terbaru') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('admin.audits.index')" wire:navigate icon="arrow-right">
                        {{ __('Semua Audit') }}
                    </flux:button>
                </div>
                <div class="mt-4 space-y-0 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    @forelse ($this->recentAudits as $audit)
                        @php $auditUrl = $this->auditUrl($audit); @endphp
                        <div class="flex gap-3 p-3">
                            <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-medium
                                @if(in_array($audit->event, ['created'])) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                @elseif(in_array($audit->event, ['updated'])) bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif(in_array($audit->event, ['deleted','rotate'])) bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400
                                @elseif(in_array($audit->event, ['restored','toggle'])) bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                @else bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 @endif
                            ">
                                {{ strtoupper(substr($audit->event,0,1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-sm">{{ $audit->event }}</span>
                                    @if ($auditUrl)
                                        <a href="{{ $auditUrl }}" wire:navigate class="text-xs text-zinc-500 hover:text-indigo-600 hover:underline dark:text-zinc-400 dark:hover:text-indigo-400">{{ class_basename($audit->auditable_type ?? '-') }} #{{ $audit->auditable_id }}</a>
                                    @else
                                        <flux:badge size="sm" color="zinc">{{ class_basename($audit->auditable_type ?? '-') }} #{{ $audit->auditable_id }}</flux:badge>
                                    @endif
                                    @if($audit->tags)<flux:badge size="sm" color="blue">{{ $audit->tags }}</flux:badge>@endif
                                </div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if($audit->user) {{ $audit->user->name }} · @endif
                                    {{ $audit->created_at?->diffForHumans() }}
                                    <span class="opacity-60">· {{ $audit->ip_address ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 text-xs text-zinc-400" title="{{ $audit->created_at }}">{{ $audit->created_at?->format('d M H:i') }}</div>
                        </div>
                    @empty
                        <div class="p-8 text-center"><flux:text>{{ __('Belum ada audit') }}</flux:text></div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @else
        <div class="flex flex-col gap-4">
            <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Selamat datang — kelola aplikasi yang terhubung ke akun Anda') }}</flux:subheading>
            <div class="mt-2 flex gap-2">
                <flux:button variant="primary" :href="route('user.authorized-apps')" wire:navigate>{{ __('Authorized apps') }}</flux:button>
                <flux:button variant="ghost" :href="route('profile.edit')" wire:navigate>{{ __('Pengaturan') }}</flux:button>
            </div>
        </div>
    @endif
</section>
