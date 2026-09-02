<?php

namespace App\Actions\Clients;

use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class CreateClient
{
    /**
     * Create a new OAuth2 client.
     */
    public function __construct(
        protected ClientRepository $clients,
    ) {}

    /**
     * Create the client and return it with its plain-text secret in memory.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Client
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'grant' => ['required', 'in:authorization_code,client_credentials'],
            'redirect' => ['required_if:grant,authorization_code', 'nullable', 'url'],
            'confidential' => ['nullable', 'boolean'],
        ])->validate();

        $name = (string) ($input['name'] ?? '');
        $grant = (string) ($input['grant'] ?? '');
        $redirect = ($input['redirect'] ?? null) === null ? null : (string) $input['redirect'];
        $confidential = ! empty($input['confidential']);

        if ($grant === 'client_credentials') {
            return $this->clients->createClientCredentialsGrantClient($name);
        }

        return $this->clients->createAuthorizationCodeGrantClient(
            $name,
            [$redirect ?? ''],
            $confidential,
        );
    }
}
