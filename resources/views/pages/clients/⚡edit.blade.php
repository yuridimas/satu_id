<?php

use App\Actions\Clients\UpdateClient;
use App\Models\OAuthClient;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit OAuth2 client')] class extends Component {
    public function mount(OAuthClient $client): void
    {
        $this->authorize('update', $client);

        $this->clientId = $client->id;
        $this->name = $client->name;
        $this->redirect = $client->redirect_uris[0] ?? '';
    }

    #[Locked]
    public string $clientId;

    public string $name = '';

    public string $redirect = '';

    /**
     * Whether the client uses the authorization code grant.
     */
    #[Computed]
    public function usesAuthorizationCode(): bool
    {
        return OAuthClient::findOrFail($this->clientId)->hasGrantType('authorization_code');
    }

    /**
     * Update the client's profile information.
     */
    public function updateClient(UpdateClient $updateClient): void
    {
        $client = OAuthClient::findOrFail($this->clientId);

        $this->authorize('update', $client);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['nullable', 'url'],
        ]);

        $updateClient->update($client, $validated);

        Flux::toast(variant: 'success', text: __('Client updated.'));

        $this->redirectRoute('admin.clients.show', $client);
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Edit OAuth2 client') }}</flux:heading>
            <flux:subheading size="lg">{{ $name }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('admin.clients.index')" wire:navigate>
            {{ __('Back to clients') }}
        </flux:button>
    </div>

    <flux:card class="mt-6 max-w-xl">
        <form wire:submit="updateClient" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" required autocomplete="off" data-test="client-name" />
                <flux:error name="name" />
            </flux:field>

            @if ($this->usesAuthorizationCode)
                <flux:field>
                    <flux:label>{{ __('Redirect URI') }}</flux:label>
                    <flux:input wire:model="redirect" type="url" placeholder="https://app.example.com/callback" data-test="client-redirect" />
                    <flux:error name="redirect" />
                </flux:field>
            @else
                <flux:text variant="subtle">
                    {{ __('Client credentials clients have no redirect URI.') }}
                </flux:text>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>