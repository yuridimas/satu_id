<?php

namespace App\Actions\Clients;

use App\Models\OAuthClient;
use App\Support\AuditLogger;
use Laravel\Passport\ClientRepository;

class RotateClientSecret
{
    /**
     * Regenerate the given client's secret.
     */
    public function __construct(
        protected ClientRepository $clients,
    ) {}

    /**
     * Rotate the secret and record an audit without storing the secret value.
     */
    public function rotate(OAuthClient $client): OAuthClient
    {
        $result = $this->clients->regenerateSecret($client);

        if ($result) {
            AuditLogger::record(
                auditable: $client,
                event: 'rotate',
                actor: null,
                tags: 'client-secret',
            );
        }

        return $client;
    }
}
