<?php

namespace App\Actions\Tokens;

use App\Models\User;
use App\Support\AuditLogger;
use Laravel\Passport\Token;
use RuntimeException;

class RevokeAccess
{
    /**
     * Revoke an access token (and its refresh token) belonging to the user.
     */
    public function revoke(User $user, string $tokenId): Token
    {
        $token = Token::query()
            ->where('user_id', $user->getKey())
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->first();

        if (! $token instanceof Token) {
            throw new RuntimeException('Access token not found.');
        }

        $token->refreshToken?->revoke();
        $token->revoke();

        AuditLogger::record(
            auditable: $user,
            event: 'revoke',
            tags: 'authorized-apps',
        );

        return $token;
    }
}
