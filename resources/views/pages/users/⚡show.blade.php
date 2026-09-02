<?php

use App\Actions\Users\ToggleUser;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Laravel\Passport\Token;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('User details')] class extends Component {
    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->userId = $user->id;
    }

    #[Locked]
    public int $userId;

    #[Computed]
    public function user(): User
    {
        return User::withTrashed()->findOrFail($this->userId);
    }

    /**
     * Toggle whether the given user is able to sign in.
     */
    public function toggle(ToggleUser $toggleUser): void
    {
        $user = $this->user;

        $this->authorize('disable', $user);

        $toggleUser->toggle($user);

        Flux::toast(variant: 'success', text: __('User status updated.'));
    }

    /**
     * The number of active OAuth access tokens owned by the user.
     */
    #[Computed]
    public function activeTokenCount(): int
    {
        return Token::query()
            ->where('user_id', $this->user->getKey())
            ->where('revoked', false)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}; ?>

<section class="w-full">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $this->user->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $this->user->name }}</flux:heading>
            <flux:subheading size="lg">{{ __('User details') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="outline" :href="route('admin.users.edit', $this->user)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>
                {{ __('Back to users') }}
            </flux:button>
        </div>
    </div>

    <flux:card class="mt-6 max-w-2xl">
        <flux:table>
            <flux:table.rows>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Name') }}</flux:table.cell>
                    <flux:table.cell>{{ $this->user->name }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Email') }}</flux:table.cell>
                    <flux:table.cell>{{ $this->user->email }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Role') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$this->user->role === UserRole::Superuser ? 'indigo' : 'zinc'" size="sm">
                            {{ $this->user->role === UserRole::Superuser ? __('Superuser') : __('User') }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Status') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($this->user->trashed())
                                <flux:badge color="rose" size="sm">{{ __('Deleted') }}</flux:badge>
                                <flux:text variant="subtle">{{ __('Restore this account from the trash to act on it.') }}</flux:text>
                            @else
                                <flux:badge :color="$this->user->active ? 'green' : 'zinc'" size="sm">
                                    {{ $this->user->active ? __('Active') : __('Inactive') }}
                                </flux:badge>

                                <flux:button
                                    wire:click="toggle"
                                    :icon="$this->user->active ? 'pause' : 'play'"
                                    variant="ghost"
                                    size="sm"
                                >
                                    {{ $this->user->active ? __('Disable') : __('Enable') }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Created') }}</flux:table.cell>
                    <flux:table.cell>{{ $this->user->created_at->toDayDateTimeString() }}</flux:table.cell>
                </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Active access tokens') }}</flux:table.cell>
                    <flux:table.cell>{{ $this->activeTokenCount }}</flux:table.cell>
                </flux:table.row>
                @if ($this->user->trashed())
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Deleted') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->user->deleted_at->toDayDateTimeString() }}</flux:table.cell>
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>
    </flux:card>
</section>