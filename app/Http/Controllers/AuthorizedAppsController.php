<?php

namespace App\Http\Controllers;

use App\Actions\Tokens\RevokeAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class AuthorizedAppsController extends Controller
{
    /**
     * Revoke an access token belonging to the authenticated user.
     */
    public function revoke(Request $request, string $token, RevokeAccess $revokeAccess): RedirectResponse
    {
        $user = $request->user();
        $tokenRow = Token::query()
            ->where('user_id', $user->getKey())
            ->where('id', $token)
            ->first();

        abort_if(! $tokenRow instanceof Token, Response::HTTP_NOT_FOUND);

        $revokeAccess->revoke($user, $token);

        return redirect()->route('user.authorized-apps')->with('status', __('Access revoked.'));
    }
}
