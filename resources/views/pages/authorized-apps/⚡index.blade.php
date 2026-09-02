<?php

use App\Actions\Tokens\RevokeAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passport\Token;

new #[Title('Authorized applications')] class extends Component {
    #[Locked]
    public ?string $revokingTokenId = null;

    #[Locked]
    public string $revokingClientName = '';

    public bool $showRevokeModal = false;

    /**
     * Open the revoke confirmation modal.
     */
    public function confirmRevoke(string $tokenId): void
    {
        $token = collect($this->activeTokens)->firstWhere('id', $tokenId);

        abort_if($token === null, 404);

        $this->revokingTokenId = $tokenId;
        $this->revokingClientName = $token['client_name'];
        $this->showRevokeModal = true;
    }

    /**
     * Revoke the access for the token confirmed by the user.
     */
    public function revoke(RevokeAccess $revokeAccess): void
    {
        if (! $this->revokingTokenId) {
            return;
        }

        $user = Auth::user();

        $revokeAccess->revoke($user, $this->revokingTokenId);

        $this->closeRevokeModal();

        $this->dispatch('$refresh');
    }

    /**
     * Close the revoke confirmation modal.
     */
    public function closeRevokeModal(): void
    {
        $this->showRevokeModal = false;
        $this->revokingTokenId = null;
        $this->revokingClientName = '';
    }

    /**
     * The active access tokens belonging to the authenticated user.
     */
    #[Computed]
    public function activeTokens(): array
    {
        $tokens = Token::query()
            ->where('user_id', Auth::id())
            ->where('revoked', false)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['client:id,name,redirect_uris,revoked'])
            ->latest('created_at')
            ->get();

        return $tokens
            ->filter(fn ($token): bool => $token->client !== null)
            ->map(fn ($token): array => [
                'id' => trim($token->getKey()),
                'client_name' => $token->client->name,
                'redirect_uri' => $token->client->redirect_uris[0] ?? null,
                'client_revoked' => (bool) $token->client->revoked,
                'created_at_diff' => $token->created_at->diffForHumans(),
                'expires_at_formatted' => $token->expires_at?->toDayDateTimeString(),
            ])
            ->values()
            ->all();
    }

    /**
     * The total number of active tokens (for the table footer).
     */
    #[Computed]
    public function activeTokenCount(): int
    {
        return count($this->activeTokens);
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Authorized applications') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Applications with access to your account') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
            {{ __('Back') }}
        </flux:button>
    </div>

    @if ($this->activeTokenCount === 0)
        <div class="mt-10 mx-auto max-w-sm rounded-2xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon.shield-check class="size-7 text-zinc-400 dark:text-zinc-500" />
            </div>
            <flux:heading>{{ __('No applications have access yet') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Log in to a client application to start using single sign-on.') }}</flux:text>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            @foreach ($this->activeTokens as $token)
                <div class="flex items-center justify-between gap-4 p-4 {{ $loop->last ? '' : 'border-b border-zinc-200 dark:border-zinc-700' }}">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <p class="font-medium tracking-tight">{{ $token['client_name'] }}</p>

                            @if ($token['client_revoked'])
                                <flux:badge color="zinc" size="sm">{{ __('Revoked') }}</flux:badge>
                            @endif
                        </div>

                        @if ($token['redirect_uri'])
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $token['redirect_uri'] }}</p>
                        @endif

                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Access granted :issued', ['issued' => $token['created_at_diff']]) }}
                            @if ($token['expires_at_formatted'])
                                · {{ __('expires :expiry', ['expiry' => $token['expires_at_formatted']]) }}
                            @endif
                        </p>
                    </div>

                    <flux:button
                        variant="ghost"
                        size="sm"
                        class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                        wire:click="confirmRevoke('{{ $token['id'] }}')"
                    >
                        {{ __('Revoke access') }}
                    </flux:button>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal
        name="revoke-token-modal"
        class="max-w-md md:min-w-md"
        @close="closeRevokeModal"
        wire:model="showRevokeModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Revoke access') }}</flux:heading>
                <flux:text>
                    {{ __('Revoke access for ":name"? The application will lose access to your account and will need you to sign in again.', ['name' => $revokingClientName]) }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeRevokeModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="revoke">
                    {{ __('Revoke access') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>