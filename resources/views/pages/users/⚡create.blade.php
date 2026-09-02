<?php

use App\Actions\Users\CreateUser;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create user')] class extends Component {
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Authorize the page when it is rendered outside of a browser request.
     */
    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    /**
     * Create the user account.
     */
    public function createUser(CreateUser $createUser): void
    {
        $this->validate([
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ]);

        $user = $createUser->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        Flux::toast(variant: 'success', text: __('User created.'));

        $this->redirectRoute('admin.users.show', $user);
    }
}; ?>

<section class="w-full">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Users') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Create user') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Create a new regular user account') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>
            {{ __('Back to users') }}
        </flux:button>
    </div>

    <flux:card class="mt-6 max-w-xl">
        <form wire:submit="createUser" class="space-y-6">
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
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                    data-test="user-password"
                />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Confirm password') }}</flux:label>
                <flux:input
                    wire:model="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Create user') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>