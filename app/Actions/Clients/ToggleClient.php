<?php

namespace App\Actions\Clients;

use App\Models\OAuthClient;
use App\Support\AuditLogger;

class ToggleClient
{
    /**
     * Revoke or re-enable the given OAuth2 client.
     */
    public function toggle(OAuthClient $client): OAuthClient
    {
        $client->forceFill(['revoked' => ! $client->revoked])->save();

        AuditLogger::record(
            auditable: $client,
            event: 'toggle',
            oldValues: ['revoked' => ! $client->revoked],
            newValues: ['revoked' => $client->revoked],
            tags: 'client-status',
        );

        return $client;
    }
}
