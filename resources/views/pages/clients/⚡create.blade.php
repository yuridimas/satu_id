<?php

use App\Actions\Clients\CreateClient;
use App\Models\OAuthClient;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create OAuth2 client')] class extends Component {
    public string $name = '';

    public string $redirect = '';

    public string $grant = 'authorization_code';

    public bool $confidential = true;

    #[Locked]
    public ?string $createdClientId = null;

    #[Locked]
    public ?string $createdSecret = null;

    /**
     * Authorize the page when it is rendered outside of a browser request.
     */
    public function mount(): void
    {
        $this->authorize('create', OAuthClient::class);
    }

    /**
     * Create the OAuth2 client.
     */
    public function createClient(CreateClient $createClient): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'grant' => ['required', 'in:authorization_code,client_credentials'],
            'redirect' => ['required_if:grant,authorization_code', 'nullable', 'url'],
            'confidential' => ['boolean'],
        ]);

        $client = $createClient->create([
            'name' => $this->name,
            'grant' => $this->grant,
            'redirect' => $validated['redirect'] ?? null,
            'confidential' => $this->confidential,
        ]);

        $this->createdClientId = $client->id;
        $this->createdSecret = $client->plainSecret ?? null;

        Flux::toast(variant: 'success', text: __('Client created.'));
    }
}; ?>

<section class="w-full">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.clients.index')" wire:navigate>{{ __('OAuth2 Clients') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Create OAuth2 client') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Register a new application') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('admin.clients.index')" wire:navigate>
            {{ __('Back to clients') }}
        </flux:button>
    </div>

    @if ($createdClientId)
        <flux:callout variant="success" icon="check-circle" class="mt-6">
            <flux:heading size="sm">Client created</flux:heading>
            <flux:text>
                {{ __('Keep these credentials safe. The secret is only shown once after creation.') }}
            </flux:text>

            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                    <span class="font-medium">{{ __('Client ID') }}</span>
                    <span class="font-mono">{{ $createdClientId }}</span>
                </div>

                @if ($createdSecret)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                        <span class="font-medium">{{ __('Client secret') }}</span>
                        <span class="font-mono" data-test="created-client-secret">{{ $createdSecret }}</span>
                    </div>
                @endif
            </div>
        </flux:callout>
    @endif

    <flux:card class="mt-6 max-w-xl">
        <form wire:submit="createClient" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" required autocomplete="off" data-test="client-name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Grant type') }}</flux:label>
                <flux:radio.group wire:model="grant" data-test="client-grant">
                    <flux:radio value="authorization_code" label="Authorization code" description="Users authorize access via a browser redirect" />
                    <flux:radio value="client_credentials" label="Client credentials" description="Server-to-server access without a user" />
                </flux:radio.group>
                <flux:error name="grant" />
            </flux:field>

            @if ($grant === 'authorization_code')
                <flux:field>
                    <flux:label>{{ __('Redirect URI') }}</flux:label>
                    <flux:input wire:model="redirect" type="url" placeholder="https://app.example.com/callback" data-test="client-redirect" />
                    <flux:error name="redirect" />
                </flux:field>

                <flux:switch wire:model="confidential" label="Confidential (require secret)" description="Public clients such as mobile apps do not send a secret" />
            @else
                <flux:text variant="subtle">
                    {{ __('Client credentials clients always use a confidential secret and have no redirect URI.') }}
                </flux:text>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="client-submit">
                    {{ __('Create client') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</section>