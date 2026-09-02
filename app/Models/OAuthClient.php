<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Laravel\Passport\Client;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property string|null $secret
 * @property string|null $provider
 * @property array<int, string> $redirect_uris
 * @property array<int, string> $grant_types
 * @property bool $revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OAuthClient extends Client implements AuditableContract
{
    use Auditable;

    /**
     * The attributes that should not be persisted to the audit log.
     *
     * @var array<int, string>
     */
    protected $auditExclude = [
        'secret',
    ];

    /**
     * Determine whether the client is active (not revoked).
     */
    public function isActive(): bool
    {
        return ! $this->revoked;
    }

    /**
     * Get the human readable grant types for the client.
     *
     * @return array<int, string>
     */
    public function grantTypeLabels(): array
    {
        return array_map(
            fn (string $grant): string => str_replace('urn:ietf:params:oauth:grant-type:', '', $grant),
            $this->grant_types,
        );
    }
}
