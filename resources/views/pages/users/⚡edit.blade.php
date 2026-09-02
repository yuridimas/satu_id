<?php

use App\Actions\Users\UpdateUser;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit user')] class extends Component {
    use ProfileValidationRules;

    #[Locked]
    public int $userId;

    #[Locked]
    public string $role;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the edit form with the given user.
     */
    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->userId = $user->id;
        $this->role = $user->role->value;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user's profile information.
     */
    public function updateUser(UpdateUser $updateUser): void
    {
        $user = User::withTrashed()->findOrFail($this->userId);

        $this->authorize('update', $user);

        $this->validate([
            'name' => $this->nameRules(),
            'email' => $this->emailRules($user->id),
        ]);

        $updateUser->update($user, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        Flux::toast(variant: 'success', text: __('User updated.'));

        $this->redirectRoute('admin.users.show', $user);
    }
}; ?>

<section class="w-full">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Edit user') }}</flux:heading>
            <flux:subheading size="lg">{{ $name }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>
            {{ __('Back to users') }}
        </flux:button>
    </div>

    <flux:card class="mt-6 max-w-xl">
        <form wire:submit="updateUser" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" required autocomplete="name" data-test="user-name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input wire:model="email" type="email" required autocomplete="email" data-test="user-email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Role') }}</flux:label>
                <flux:input :value="$role" readonly disabled />
                <flux:text variant="subtle">{{ __('Role cannot be changed from the UI.') }}</flux:text>
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>