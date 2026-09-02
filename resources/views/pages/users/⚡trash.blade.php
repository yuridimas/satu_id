<?php

use App\Actions\Users\RestoreUser;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Trashed users')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $searchDraft = '';

    /**
     * Authorize the page when it is rendered outside of a browser request.
     */
    public function mount(): void
    {
        $this->authorize('restoreInTrash', User::class);
        $this->searchDraft = $this->search;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->search = $this->searchDraft;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->searchDraft = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => User::onlyTrashed()->where('role', UserRole::User)->count(),
        ];
    }

    /**
     * Restore the given soft deleted user.
     */
    public function restore(int $userId, RestoreUser $restoreUser): void
    {
        $user = User::withTrashed()->findOrFail($userId);

        $this->authorize('restore', $user);

        $restoreUser->restore($user);

        Flux::toast(variant: 'success', text: __('User restored.'));
    }

    /**
     * The paginated list of soft deleted regular users.
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::onlyTrashed()
            ->where('role', UserRole::User)
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('email', 'ilike', '%'.$this->search.'%');
                });
            })
            ->latest('deleted_at')
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    {{-- 1) breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Trash') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- 2) page-header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users — Trash') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Restore a soft deleted user account') }}</flux:subheading>
        </div>
        <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left">
            {{ __('Back to users') }}
        </flux:button>
    </div>

    {{-- 3) card-stat --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Total Trash') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-amber-600">{{ $this->stats['total'] }}</div>
        </flux:card>
    </div>

    {{-- 4) card-filter --}}
    <flux:card class="mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <flux:field class="flex-1">
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input type="search" wire:model="searchDraft" :placeholder="__('Search name or email…')" icon="magnifying-glass" />
            </flux:field>
            <div class="flex gap-2 sm:pb-0.5">
                <flux:button variant="primary" wire:click="applyFilters" icon="funnel">{{ __('Terapkan') }}</flux:button>
                <flux:button variant="ghost" wire:click="resetFilters" icon="x-mark">{{ __('Reset') }}</flux:button>
            </div>
        </div>
    </flux:card>

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-[30%] first:!ps-4">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="w-[35%]">{{ __('Email') }}</flux:table.column>
                <flux:table.column class="w-[150px]">{{ __('Deleted') }}</flux:table.column>
                <flux:table.column class="w-[160px] text-right last:!pe-4">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell class="font-medium first:!ps-4">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate" :title="$user->email">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap" :title="$user->deleted_at->toDayDateTimeString()">{{ $user->deleted_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="text-right last:!pe-4">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button
                                    :href="route('admin.users.show', $user)"
                                    wire:navigate
                                    icon="eye"
                                    tooltip="{{ __('View') }}"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    wire:click="restore({{ $user->id }})"
                                    icon="arrow-path"
                                    tooltip="{{ __('Restore') }}"
                                    variant="ghost"
                                    size="sm"
                                >
                                    {{ __('Restore') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="py-10 text-center">
                                <flux:heading>{{ __('Trash is empty') }}</flux:heading>
                                <flux:text class="mt-1">{{ __('No deleted users to restore.') }}</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $this->users->links() }}
    </div>
</section>