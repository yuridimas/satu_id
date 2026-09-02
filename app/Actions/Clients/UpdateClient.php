<?php

namespace App\Actions\Clients;

use App\Models\OAuthClient;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\ClientRepository;

class UpdateClient
{
    /**
     * Update the given OAuth2 client.
     */
    public function __construct(
        protected ClientRepository $clients,
    ) {}

    /**
     * Update the client's name and redirect URIs.
     *
     * @param  array<string, string|null>  $input
     */
    public function update(OAuthClient $client, array $input): OAuthClient
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['nullable', 'url'],
        ])->validate();

        $redirectUris = [];

        if ($client->hasGrantType('authorization_code') && is_string($input['redirect'] ?? null) && trim($input['redirect']) !== '') {
            $redirectUris[] = $input['redirect'];
        }

        $this->clients->update($client, $input['name'], $redirectUris);

        return $client->refresh();
    }
}
