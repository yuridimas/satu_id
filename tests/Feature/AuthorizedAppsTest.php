<?php

use App\Models\Audit;
use App\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

function makeAccessTokenFor(User $user, $client, array $attributes = []): Token
{
    return Token::query()->create(array_merge([
        'id' => str_repeat('d', 40),
        'user_id' => $user->id,
        'client_id' => $client->id,
        'scopes' => '[]',
        'revoked' => false,
        'expires_at' => now()->addDay(),
    ], $attributes));
}

test('guests are redirected away from the authorized apps page', function () {
    $this->get(route('user.authorized-apps'))
        ->assertRedirect(route('login'));
});

test('active tokens are listed with client and expiry information', function () {
    $user = User::factory()->create();
    $client = createOAuthClient('App A (Web)', ['https://app-a.com/callback']);

    makeAccessTokenFor($user, $client);

    $this->actingAs($user)
        ->get(route('user.authorized-apps'))
        ->assertOk()
        ->assertSee('App A (Web)')
        ->assertSee('https://app-a.com/callback');
});

test('expired and revoked tokens are not listed', function () {
    $user = User::factory()->create();
    $client = createOAuthClient();

    makeAccessTokenFor($user, $client, ['revoked' => true, 'id' => str_repeat('e', 40)]);
    makeAccessTokenFor($user, $client, ['expires_at' => now()->subDay(), 'id' => str_repeat('f', 40)]);

    $this->actingAs($user)
        ->get(route('user.authorized-apps'))
        ->assertOk()
        ->assertSee('No applications have access yet');
});

test('client credentials tokens without a user are not listed', function () {
    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Service App');

    Token::query()->create([
        'id' => str_repeat('g', 40),
        'user_id' => null,
        'client_id' => $client->id,
        'scopes' => '[]',
        'revoked' => false,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('user.authorized-apps'))
        ->assertOk()
        ->assertDontSee('Service App');
});

test('a user can revoke the access of their own application', function () {
    $user = User::factory()->create();
    $client = createOAuthClient('App A (Web)');

    $token = makeAccessTokenFor($user, $client);
    $refresh = RefreshToken::query()->create([
        'id' => 'h'.str_repeat('i', 39),
        'access_token_id' => $token->id,
        'revoked' => false,
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->post(route('user.authorized-apps.revoke', $token))
        ->assertRedirect(route('user.authorized-apps'));

    expect($token->refresh()->revoked)->toBeTrue()
        ->and($refresh->refresh()->revoked)->toBeTrue();

    $this->actingAs($user)
        ->get(route('user.authorized-apps'))
        ->assertDontSee('App A (Web)');
});

test('a user cannot revoke another users token', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $client = createOAuthClient();

    $otherToken = makeAccessTokenFor($other, $client);

    $this->actingAs($user)
        ->post(route('user.authorized-apps.revoke', $otherToken))
        ->assertNotFound();

    expect($otherToken->refresh()->revoked)->toBeFalse();
});

test('revoking access is recorded in the audit log', function () {
    $user = User::factory()->create();
    $client = createOAuthClient();

    $token = makeAccessTokenFor($user, $client);

    $this->actingAs($user)
        ->post(route('user.authorized-apps.revoke', $token));

    expect(Audit::query()
        ->where('auditable_type', User::class)
        ->where('event', 'revoke')
        ->where('tags', 'authorized-apps')
        ->exists())->toBeTrue();
});

test('the authorized apps page livewire component revokes the app access', function () {
    $user = User::factory()->create();
    $client = createOAuthClient('App B (Mobile)');

    $token = makeAccessTokenFor($user, $client);

    Livewire::actingAs($user)
        ->test('pages::authorized-apps.index')
        ->assertSee('App B (Mobile)')
        ->call('confirmRevoke', $token->id)
        ->call('revoke')
        ->assertDontSee('App B (Mobile)');

    expect($token->refresh()->revoked)->toBeTrue();
});
