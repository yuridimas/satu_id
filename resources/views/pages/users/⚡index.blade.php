<?php

use App\Actions\Users\DeleteUser;
use App\Actions\Users\ToggleUser;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $searchDraft = '';

    public string $statusDraft = 'all';

    #[Locked]
    public ?int $deletingUserId = null;

    #[Locked]
    public string $deletingUserName = '';

    public bool $showDeleteModal = false;

    /**
     * Authorize the page when it is rendered outside of a browser request.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
        $this->searchDraft = $this->search;
        $this->statusDraft = $this->status;
    }

    /**
     * Reset pagination when the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when the status filter changes.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->search = $this->searchDraft;
        $this->status = $this->statusDraft;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->searchDraft = '';
        $this->statusDraft = 'all';
        $this->search = '';
        $this->status = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => User::where('role', UserRole::User)->count(),
            'active' => User::where('role', UserRole::User)->where('active', true)->count(),
            'inactive' => User::where('role', UserRole::User)->where('active', false)->count(),
            'trash' => User::onlyTrashed()->where('role', UserRole::User)->count(),
        ];
    }

    /**
     * Toggle whether the given user is able to sign in.
     */
    public function toggle(int $userId, ToggleUser $toggleUser): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('disable', $user);

        $toggleUser->toggle($user);

        Flux::toast(variant: 'success', text: __('User status updated.'));
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
        $this->showDeleteModal = true;
    }

    /**
     * Soft delete the user confirmed by the operator.
     */
    public function delete(DeleteUser $deleteUser): void
    {
        if (! $this->deletingUserId) {
            return;
        }

        $user = User::findOrFail($this->deletingUserId);

        $this->authorize('delete', $user);

        $deleteUser->delete($user);

        $this->closeDeleteModal();

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUserId = null;
        $this->deletingUserName = '';
    }

    /**
     * The paginated, filtered list of regular users.
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->where('role', UserRole::User)
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('email', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('active', false))
            ->latest()
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    {{-- 1) breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- 2) page-header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Manage regular user accounts') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('admin.exports.show', 'users')" wire:navigate icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
            <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate icon="plus">
                {{ __('New user') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.users.trash')" wire:navigate icon="trash">
                {{ __('Trash') }} ({{ $this->stats['trash'] }})
            </flux:button>
        </div>
    </div>

    {{-- 3) card-stat (opsional) --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Total') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ $this->stats['total'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Active') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-green-600">{{ $this->stats['active'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Inactive') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-zinc-500">{{ $this->stats['inactive'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Trash') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-amber-600">{{ $this->stats['trash'] }}</div>
        </flux:card>
    </div>

    {{-- 4) card-filter (opsional) --}}
    <flux:card class="mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <flux:field class="flex-1">
                    <flux:label>{{ __('Search') }}</flux:label>
                    <flux:input
                        type="search"
                        wire:model="searchDraft"
                        :placeholder="__('Search name or email…')"
                        icon="magnifying-glass"
                    />
                </flux:field>
                <flux:field class="sm:w-40">
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model="statusDraft">
                        <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
            <div class="flex gap-2 sm:pb-0.5">
                <flux:button variant="primary" wire:click="applyFilters" icon="funnel">
                    {{ __('Terapkan') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="resetFilters" icon="x-mark">
                    {{ __('Reset') }}
                </flux:button>
            </div>
        </div>
    </flux:card>

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-[26%] first:!ps-4">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="w-[34%]">{{ __('Email') }}</flux:table.column>
                <flux:table.column class="w-[110px]">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-[150px]">{{ __('Created') }}</flux:table.column>
                <flux:table.column class="w-[140px] text-right last:!pe-4">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell class="first:!ps-4">
                            <a
                                :href="route('admin.users.show', $user)"
                                wire:navigate
                                class="font-medium text-zinc-900 hover:text-zinc-600 hover:underline dark:text-zinc-100 dark:hover:text-zinc-300"
                            >
                                {{ $user->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate" :title="$user->email">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$user->active ? 'green' : 'zinc'" size="sm">
                                {{ $user->active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap" :title="$user->created_at->toDayDateTimeString()">{{ $user->created_at->diffForHumans() }}</flux:table.cell>
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
                                    :href="route('admin.users.edit', $user)"
                                    wire:navigate
                                    icon="pencil-square"
                                    tooltip="{{ __('Edit') }}"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    wire:click="toggle({{ $user->id }})"
                                    :icon="$user->active ? 'pause' : 'play'"
                                    :tooltip="$user->active ? __('Disable') : __('Enable')"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    wire:click="confirmDelete({{ $user->id }})"
                                    icon="trash"
                                    icon:variant="outline"
                                    tooltip="{{ __('Delete') }}"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="py-10 text-center">
                                <flux:heading>{{ __('No users found') }}</flux:heading>
                                <flux:text class="mt-1">{{ __('Try adjusting your search or filters.') }}</flux:text>
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

    <flux:modal
        name="delete-user-modal"
        class="max-w-md md:min-w-md"
        @close="closeDeleteModal"
        wire:model="showDeleteModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
                <flux:text>
                    {{ __('Are you sure you want to delete ":name"? Their account will move to the trash and all active access tokens will be revoked.', ['name' => $deletingUserName]) }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeDeleteModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete user') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>