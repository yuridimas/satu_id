<?php

use App\Models\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Audit Logs')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $event = 'all';
    public string $type = 'all';

    public string $searchDraft = '';
    public string $eventDraft = 'all';
    public string $typeDraft = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperuser(), 403);
        $this->searchDraft = $this->search;
        $this->eventDraft = $this->event;
        $this->typeDraft = $this->type;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->search = $this->searchDraft;
        $this->event = $this->eventDraft;
        $this->type = $this->typeDraft;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->searchDraft = '';
        $this->eventDraft = 'all';
        $this->typeDraft = 'all';
        $this->search = '';
        $this->event = 'all';
        $this->type = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Audit::count(),
            'today' => Audit::whereDate('created_at', today())->count(),
            'users' => Audit::where('auditable_type', 'like', '%User%')->count(),
            'clients' => Audit::where('auditable_type', 'like', '%OAuthClient%')->count(),
        ];
    }

    #[Computed]
    public function audits(): LengthAwarePaginator
    {
        return Audit::with('user')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('event', 'ilike', '%'.$this->search.'%')
                        ->orWhere('auditable_type', 'ilike', '%'.$this->search.'%')
                        ->orWhere('tags', 'ilike', '%'.$this->search.'%')
                        ->orWhere('ip_address', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->event !== 'all', fn($q) => $q->where('event', $this->event))
            ->when($this->type !== 'all', fn($q) => $q->where('auditable_type', 'ilike', '%'.$this->type.'%'))
            ->latest('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function events(): array
    {
        return Audit::select('event')->distinct()->pluck('event')->filter()->sort()->values()->all();
    }
}; ?>

<section class="w-full">
    {{-- 1) breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Audit Logs') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- 2) page-header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Audit Logs') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Jejak aktivitas user & client — tanpa secret') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('admin.exports.show', 'audits')" wire:navigate icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('dashboard')" wire:navigate icon="arrow-left">
                {{ __('Back to dashboard') }}
            </flux:button>
        </div>
    </div>

    {{-- 3) card-stat --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Total') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ $this->stats['total'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Hari ini') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-green-600">{{ $this->stats['today'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('User logs') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ $this->stats['users'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Client logs') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ $this->stats['clients'] }}</div>
        </flux:card>
    </div>

    {{-- 4) card-filter --}}
    <flux:card class="mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <flux:field class="flex-1">
                    <flux:label>{{ __('Search') }}</flux:label>
                    <flux:input type="search" wire:model="searchDraft" :placeholder="__('Search event, type, IP…')" icon="magnifying-glass" />
                </flux:field>
                <flux:field class="sm:w-32">
                    <flux:label>{{ __('Event') }}</flux:label>
                    <flux:select wire:model="eventDraft">
                        <flux:select.option value="all">{{ __('All events') }}</flux:select.option>
                        @foreach ($this->events as $ev)
                            <flux:select.option value="{{ $ev }}">{{ $ev }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field class="sm:w-40">
                    <flux:label>{{ __('Type') }}</flux:label>
                    <flux:select wire:model="typeDraft">
                        <flux:select.option value="all">{{ __('All types') }}</flux:select.option>
                        <flux:select.option value="User">{{ __('User') }}</flux:select.option>
                        <flux:select.option value="OAuthClient">{{ __('OAuthClient') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
            <div class="flex gap-2 sm:pb-0.5">
                <flux:button variant="primary" wire:click="applyFilters" icon="funnel">{{ __('Terapkan') }}</flux:button>
                <flux:button variant="ghost" wire:click="resetFilters" icon="x-mark">{{ __('Reset') }}</flux:button>
            </div>
        </div>
    </flux:card>

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-[160px] first:!ps-4">{{ __('Waktu') }}</flux:table.column>
                <flux:table.column class="w-[110px]">{{ __('Event') }}</flux:table.column>
                <flux:table.column class="w-[160px]">{{ __('Auditable') }}</flux:table.column>
                <flux:table.column class="w-[160px]">{{ __('Actor') }}</flux:table.column>
                <flux:table.column class="w-[140px]">{{ __('IP') }}</flux:table.column>
                <flux:table.column class="w-[120px] last:!pe-4">{{ __('Tags') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->audits as $audit)
                    <flux:table.row :key="$audit->id">
                        <flux:table.cell class="first:!ps-4 whitespace-nowrap" :title="$audit->created_at">
                            <div class="text-xs">{{ $audit->created_at?->diffForHumans() }}</div>
                            <div class="text-[11px] text-zinc-500">{{ $audit->created_at?->format('d M Y H:i') }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($audit->event){'created'=>'green','updated'=>'blue','deleted','rotate'=>'rose','restored','toggle'=>'orange',default=>'zinc'}" size="sm">{{ $audit->event }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate text-xs">
                            <div class="truncate font-medium" title="{{ $audit->auditable_type }}">{{ class_basename($audit->auditable_type ?? '-') }} #{{ $audit->auditable_id }}</div>
                            <div class="truncate text-[11px] text-zinc-500">{{ Str::limit(json_encode($audit->new_values ?? $audit->old_values), 60) }}</div>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate text-xs" :title="$audit->user?->email">
                            {{ $audit->user?->name ?? __('System') }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs font-mono">{{ $audit->ip_address ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="last:!pe-4">
                            @if($audit->tags)
                                <flux:badge color="blue" size="sm">{{ $audit->tags }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6"><div class="py-10 text-center"><flux:heading>{{ __('Belum ada audit') }}</flux:heading><flux:text class="mt-1">{{ __('Aktivitas akan muncul di sini.') }}</flux:text></div></flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $this->audits->links() }}
    </div>
</section>
