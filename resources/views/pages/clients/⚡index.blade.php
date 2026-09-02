<?php

use App\Actions\Clients\DeleteClient;
use App\Actions\Clients\ToggleClient;
use App\Models\OAuthClient;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('OAuth2 Clients')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $searchDraft = '';

    public string $statusDraft = 'all';

    #[Locked]
    public ?string $deletingClientId = null;

    #[Locked]
    public string $deletingClientName = '';

    public bool $showDeleteModal = false;

    /**
     * Authorize the page when it is rendered outside of a browser request.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', OAuthClient::class);
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
            'total' => OAuthClient::count(),
            'active' => OAuthClient::where('revoked', false)->count(),
            'revoked' => OAuthClient::where('revoked', true)->count(),
        ];
    }

    /**
     * Toggle whether the given client can issue new tokens.
     */
    public function toggle(string $clientId, ToggleClient $toggleClient): void
    {
        $client = OAuthClient::findOrFail($clientId);

        $this->authorize('toggle', $client);

        $toggleClient->toggle($client);

        Flux::toast(variant: 'success', text: __('Client status updated.'));
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(string $clientId): void
    {
        $client = OAuthClient::findOrFail($clientId);

        $this->authorize('delete', $client);

        $this->deletingClientId = $client->id;
        $this->deletingClientName = $client->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete (revoke) the client confirmed by the operator.
     */
    public function delete(DeleteClient $deleteClient): void
    {
        if (! $this->deletingClientId) {
            return;
        }

        $client = OAuthClient::findOrFail($this->deletingClientId);

        $this->authorize('delete', $client);

        $deleteClient->delete($client);

        $this->closeDeleteModal();

        Flux::toast(variant: 'success', text: __('Client deleted.'));
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingClientId = null;
        $this->deletingClientName = '';
    }

    /**
     * The paginated, filtered list of OAuth2 clients.
     */
    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return OAuthClient::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'))
            ->when($this->status === 'active', fn ($query) => $query->where('revoked', false))
            ->when($this->status === 'revoked', fn ($query) => $query->where('revoked', true))
            ->latest('updated_at')
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    {{-- 1) breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('OAuth2 Clients') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- 2) page-header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('OAuth2 Clients') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Manage applications allowed to sign in users') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('admin.exports.show', 'clients')" wire:navigate icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
            <flux:button variant="primary" :href="route('admin.clients.create')" wire:navigate icon="plus">
                {{ __('New client') }}
            </flux:button>
        </div>
    </div>

    {{-- 3) card-stat --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Total') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ $this->stats['total'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Active') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-green-600">{{ $this->stats['active'] }}</div>
        </flux:card>
        <flux:card class="py-4">
            <flux:text variant="subtle" class="text-xs uppercase tracking-wide">{{ __('Revoked') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold text-zinc-500">{{ $this->stats['revoked'] }}</div>
        </flux:card>
    </div>

    {{-- 4) card-filter --}}
    <flux:card class="mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <flux:field class="flex-1">
                    <flux:label>{{ __('Search') }}</flux:label>
                    <flux:input type="search" wire:model="searchDraft" :placeholder="__('Search clients…')" icon="magnifying-glass" />
                </flux:field>
                <flux:field class="sm:w-40">
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model="statusDraft">
                        <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="revoked">{{ __('Revoked') }}</flux:select.option>
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
                <flux:table.column class="w-[22%] first:!ps-4">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="w-[30%]">{{ __('Redirect URI') }}</flux:table.column>
                <flux:table.column class="w-[20%]">{{ __('Grant types') }}</flux:table.column>
                <flux:table.column class="w-[110px]">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-[140px] text-right last:!pe-4">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->clients as $client)
                    @php
                        $grantLabels = [
                            'authorization_code' => __('Authorization Code'),
                            'client_credentials' => __('Client Credentials'),
                            'refresh_token' => __('Refresh Token'),
                            'personal_access' => __('Personal Access'),
                            'password' => __('Password'),
                        ];
                        $grantDisplay = implode(', ', array_map(fn (string $type): string => $grantLabels[$type] ?? $type, $client->grant_types ?? []));
                    @endphp
                    <flux:table.row :key="$client->id">
                        <flux:table.cell class="first:!ps-4">
                            <a
                                :href="route('admin.clients.show', $client)"
                                wire:navigate
                                class="font-medium text-zinc-900 hover:text-zinc-600 hover:underline dark:text-zinc-100 dark:hover:text-zinc-300"
                            >
                                {{ $client->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate text-xs" :title="$client->redirect_uris[0] ?? ''">
                            {{ $client->redirect_uris[0] ?? __('—') }}
                        </flux:table.cell>
                        <flux:table.cell class="max-w-0 truncate text-xs" :title="$grantDisplay">{{ $grantDisplay }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$client->isActive() ? 'green' : 'zinc'" size="sm">
                                {{ $client->isActive() ? __('Active') : __('Revoked') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right last:!pe-4">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button
                                    :href="route('admin.clients.show', $client)"
                                    wire:navigate
                                    icon="eye"
                                    tooltip="{{ __('View') }}"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    :href="route('admin.clients.edit', $client)"
                                    wire:navigate
                                    icon="pencil-square"
                                    tooltip="{{ __('Edit') }}"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    wire:click="toggle('{{ $client->id }}')"
                                    :icon="$client->isActive() ? 'pause' : 'play'"
                                    :tooltip="$client->isActive() ? __('Revoke') : __('Re-enable')"
                                    variant="ghost"
                                    size="sm"
                                />
                                <flux:button
                                    wire:click="confirmDelete('{{ $client->id }}')"
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
                                <flux:heading>{{ __('No clients found') }}</flux:heading>
                                <flux:text class="mt-1">{{ __('Try adjusting your search or filters.') }}</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $this->clients->links() }}
    </div>

    <flux:modal
        name="delete-client-modal"
        class="max-w-md md:min-w-md"
        @close="closeDeleteModal"
        wire:model="showDeleteModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Delete client') }}</flux:heading>
                <flux:text>
                    {{ __('Are you sure you want to delete ":name"? The client will be revoked and every related access and refresh token will be revoked.', ['name' => $deletingClientName]) }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeDeleteModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete client') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>