<?php

use App\Actions\Clients\RotateClientSecret;
use App\Actions\Clients\ToggleClient;
use App\Concerns\PasswordValidationRules;
use App\Models\OAuthClient;
use Flux\Flux;
use Laravel\Passport\Token;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('OAuth2 client details')] class extends Component {
    use PasswordValidationRules;

    public function mount(OAuthClient $client): void
    {
        $this->authorize('view', $client);

        $this->clientId = $client->id;
        $this->revealedSecret = session()->pull('client_secret');
    }

    #[Locked]
    public string $clientId;

    #[Locked]
    public ?string $revealedSecret = null;

    public string $guideTab = 'php';

    public string $rotatePassword = '';

    public bool $showRotateModal = false;

    public function setGuideTab(string $tab): void
    {
        $this->guideTab = $tab;
    }

    public function confirmRotate(): void
    {
        $this->reset('rotatePassword');
        $this->showRotateModal = true;
    }

    public function closeRotateModal(): void
    {
        $this->showRotateModal = false;
        $this->reset('rotatePassword');
        $this->resetValidation();
    }

    public function rotate(RotateClientSecret $rotateClientSecret): void
    {
        $this->validate([
            'rotatePassword' => $this->currentPasswordRules(),
        ]);

        $client = $this->client;

        $this->authorize('rotate', $client);

        $rotateClientSecret->rotate($client);

        $this->revealedSecret = $client->plainSecret;
        $this->showRotateModal = false;
        $this->reset('rotatePassword');

        Flux::toast(variant: 'success', text: __('Client secret rotated.'));
    }

    /**
     * The client being displayed.
     */
    #[Computed]
    public function client(): OAuthClient
    {
        return OAuthClient::findOrFail($this->clientId);
    }

    /**
     * Toggle whether the client can issue new tokens.
     */
    public function toggle(ToggleClient $toggleClient): void
    {
        $client = $this->client;

        $this->authorize('toggle', $client);

        $toggleClient->toggle($client);

        Flux::toast(variant: 'success', text: __('Client status updated.'));
    }

    /**
     * The number of active access tokens issued to this client.
     */
    #[Computed]
    public function activeTokenCount(): int
    {
        return Token::query()
            ->where('client_id', $this->clientId)
            ->where('revoked', false)
            ->count();
    }

    /**
     * Human readable labels for the client's grant types.
     */
    #[Computed]
    public function grantTypeLabels(): string
    {
        $labels = [
            'authorization_code' => __('Authorization Code'),
            'client_credentials' => __('Client Credentials'),
            'refresh_token' => __('Refresh Token'),
            'personal_access' => __('Personal Access'),
            'password' => __('Password'),
        ];

        return implode(', ', array_map(
            fn (string $type): string => $labels[$type] ?? $type,
            $this->client->grant_types ?? [],
        ));
    }
}; ?>

<section class="w-full">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.clients.index')" wire:navigate>{{ __('OAuth2 Clients') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $this->client->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $this->client->name }}</flux:heading>
            <flux:subheading size="lg">{{ __('OAuth2 client details') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="outline" :href="route('admin.clients.edit', $this->client)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.clients.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    @if ($revealedSecret)
        <flux:callout variant="success" icon="check-circle" class="mt-6">
            <flux:heading size="sm">{{ __('New client secret') }}</flux:heading>
            <flux:text>
                {{ __('The previous secret is now invalid. This secret is only shown once.') }}
            </flux:text>

            <div
                class="mt-3 flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800"
                x-data="{
                    copied: false,
                    async copy() {
                        try {
                            await navigator.clipboard.writeText('{{ $revealedSecret }}');
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1500);
                        } catch (e) {
                            console.warn('Could not copy to clipboard');
                        }
                    }
                }"
            >
                <span class="font-mono break-all flex-1" data-test="client-secret">{{ $revealedSecret }}</span>
                <flux:button x-show="!copied" @click="copy()" icon="clipboard-document" tooltip="{{ __('Copy') }}" variant="ghost" size="sm" data-test="copy-secret" />
                <flux:icon.check x-show="copied" variant="solid" class="size-5 text-green-600" />
            </div>
        </flux:callout>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <flux:card class="lg:col-span-2">
            <flux:table>
                <flux:table.rows>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Client ID') }}</flux:table.cell>
                        <flux:table.cell class="break-all font-mono">{{ $this->client->id }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Redirect URIs') }}</flux:table.cell>
                        <flux:table.cell>
                            @if (count($this->client->redirect_uris) > 1)
                                <ul class="flex flex-col gap-1">
                                    @foreach ($this->client->redirect_uris as $uri)
                                        <li class="break-all font-mono">{{ $uri }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="break-all font-mono">{{ $this->client->redirect_uris[0] ?? __('—') }}</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Grant types') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->grantTypeLabels }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Confidential') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->client->confidential() ? __('Yes') : __('No') }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Status') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$this->client->isActive() ? 'green' : 'zinc'" size="sm">
                                {{ $this->client->isActive() ? __('Active') : __('Revoked') }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                <flux:table.row>
                    <flux:table.cell class="w-[180px] font-medium">{{ __('Client secret') }}</flux:table.cell>
                    <flux:table.cell class="!whitespace-normal">
                        <span class="font-mono">••••••••</span>
                        <flux:text variant="subtle" class="mt-1 block text-xs !whitespace-normal break-words text-wrap">{{ __('The secret is stored as a hash. It is shown as plain text only once in the notification above after rotation.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Created') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->client->created_at->toDayDateTimeString() }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="w-[180px] font-medium">{{ __('Active access tokens') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->activeTokenCount }}</flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="lg:col-span-3 lg:sticky lg:top-6">
            <div class="flex gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                    <flux:icon.code-bracket class="size-5" />
                </div>
                <div>
                    <flux:heading size="sm">{{ __('Integration guide') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('OAuth2 examples in several languages — update the environment for your client.') }}</flux:text>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-1.5">
                <flux:button :variant="$guideTab === 'php' ? 'primary' : 'ghost'" size="sm" wire:click="setGuideTab('php')">PHP</flux:button>
                <flux:button :variant="$guideTab === 'js' ? 'primary' : 'ghost'" size="sm" wire:click="setGuideTab('js')">JS</flux:button>
                <flux:button :variant="$guideTab === 'python' ? 'primary' : 'ghost'" size="sm" wire:click="setGuideTab('python')">Python</flux:button>
                <flux:button :variant="$guideTab === 'curl' ? 'primary' : 'ghost'" size="sm" wire:click="setGuideTab('curl')">cURL</flux:button>
                <flux:button :variant="$guideTab === 'go' ? 'primary' : 'ghost'" size="sm" wire:click="setGuideTab('go')">Go</flux:button>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="overflow-x-auto bg-zinc-950 p-4">
                    @if ($guideTab === 'php')
<pre class="text-xs leading-relaxed text-zinc-100"><code>// Laravel HTTP - Authorization Code
use Illuminate\Support\Facades\Http;

$response = Http::asForm()->post('{{ url('/oauth/token') }}', [
    'grant_type' => 'authorization_code',
    'client_id' => '{{ $this->client->id }}',
    'client_secret' => env('SATU_CLIENT_SECRET'),
    'redirect_uri' => '{{ $this->client->redirect_uris[0] ?? 'https://app.example.com/callback' }}',
    'code' => $code,
]);
$token = $response->json('access_token');</code></pre>
                    @elseif ($guideTab === 'js')
<pre class="text-xs leading-relaxed text-zinc-100"><code>// JavaScript (fetch)
const res = await fetch('{{ url('/oauth/token') }}', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    grant_type: 'authorization_code',
    client_id: '{{ $this->client->id }}',
    client_secret: process.env.SATU_CLIENT_SECRET,
    redirect_uri: '{{ $this->client->redirect_uris[0] ?? 'https://app.example.com/callback' }}',
    code,
  }),
});
const { access_token } = await res.json();</code></pre>
                    @elseif ($guideTab === 'python')
<pre class="text-xs leading-relaxed text-zinc-100"><code># Python (requests)
import requests, os

res = requests.post('{{ url('/oauth/token') }}', data={
    'grant_type': 'authorization_code',
    'client_id': '{{ $this->client->id }}',
    'client_secret': os.getenv('SATU_CLIENT_SECRET'),
    'redirect_uri': '{{ $this->client->redirect_uris[0] ?? 'https://app.example.com/callback' }}',
    'code': code,
})
token = res.json()['access_token']</code></pre>
                    @elseif ($guideTab === 'curl')
<pre class="text-xs leading-relaxed text-zinc-100"><code># cURL
curl -X POST {{ url('/oauth/token') }} \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code" \
  -d "client_id={{ $this->client->id }}" \
  -d "client_secret=$SATU_CLIENT_SECRET" \
  -d "redirect_uri={{ $this->client->redirect_uris[0] ?? 'https://app.example.com/callback' }}" \
  -d "code=$CODE"</code></pre>
                    @elseif ($guideTab === 'go')
<pre class="text-xs leading-relaxed text-zinc-100"><code>// Go (net/http)
data := url.Values{}
data.Set("grant_type", "authorization_code")
data.Set("client_id", "{{ $this->client->id }}")
data.Set("client_secret", os.Getenv("SATU_CLIENT_SECRET"))
data.Set("redirect_uri", "{{ $this->client->redirect_uris[0] ?? 'https://app.example.com/callback' }}")
data.Set("code", code)
resp, _ := http.PostForm("{{ url('/oauth/token') }}", data)
defer resp.Body.Close()</code></pre>
                    @endif
                    </div>
            </div>

            <flux:text variant="subtle" class="mt-3 text-xs">
                {{ __('The default scope is empty. Add a scope to the authorization URL: /oauth/authorize?client_id=...&scope=...') }}
            </flux:text>
        </flux:card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <flux:card>
            <div class="flex gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.shield-exclamation class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:heading size="sm">{{ __('Client status controls') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ __('Revoking a client stops new token issuance. Active tokens remain valid until they expire. Re-enable it to reactivate it without creating a new client.') }}
                    </flux:text>
                </div>
            </div>
            <div class="mt-4 flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button
                    wire:click="toggle"
                    :icon="$this->client->isActive() ? 'pause' : 'play'"
                    :variant="$this->client->isActive() ? 'danger' : 'primary'"
                    size="sm"
                >
                    {{ $this->client->isActive() ? __('Revoke client') : __('Re-enable client') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon.key class="size-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:heading size="sm">{{ __('Manage client secret') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ __('Rotation generates a new 40-character secret. The old secret becomes invalid immediately. Password confirmation is required.') }}
                    </flux:text>
                </div>
            </div>
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-xs">{{ __('The secret is shown once in the notification above after rotation. Save it in .env immediately. Use Reveal (eye icon) in the table above to view it again without rotating.') }}</flux:text>
                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" size="sm" icon="arrow-path" wire:click="confirmRotate" data-test="rotate-secret">
                        {{ __('Rotate secret') }}
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:modal name="rotate-secret-modal" class="max-w-md" wire:model="showRotateModal" @close="closeRotateModal">
        <form wire:submit="rotate" class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Confirm password to rotate the secret') }}</flux:heading>
                <flux:text>
                    {{ __('Enter your password to confirm the rotation. The old secret will become invalid, and the new secret will be shown once.') }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input wire:model="rotatePassword" type="password" required autocomplete="current-password" viewable data-test="rotate-password-input" />
                <flux:error name="rotatePassword" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeRotateModal" type="button">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit" icon="arrow-path">{{ __('Rotate secret') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>