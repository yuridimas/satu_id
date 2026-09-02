<?php

namespace App\Actions\Clients;

use App\Models\OAuthClient;
use App\Support\AuditLogger;
use Laravel\Passport\ClientRepository;

class DeleteClient
{
    /**
     * Revoke the given client and all of its tokens.
     */
    public function __construct(
        protected ClientRepository $clients,
    ) {}

    /**
     * Delete (revoke) the client and revoke every related access/refresh token.
     */
    public function delete(OAuthClient $client): void
    {
        $this->clients->delete($client);

        AuditLogger::record(
            auditable: $client,
            event: 'deleted',
            tags: 'client',
        );
    }
}
