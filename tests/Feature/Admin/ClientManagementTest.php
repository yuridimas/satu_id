<?php

use App\Models\Audit;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

test('regular users cannot access client management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.clients.index'))
        ->assertForbidden();
});

test('superuser can view the clients index page', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('App A (Web)');

    $this->actingAs($superuser)
        ->get(route('admin.clients.index'))
        ->assertOk()
        ->assertSee('App A (Web)');
});

test('superuser can create an authorization code client', function () {
    $superuser = createSuperuser();

    $response = $this->actingAs($superuser)->post(route('admin.clients.store'), [
        'name' => 'App A (Web)',
        'grant' => 'authorization_code',
        'redirect' => 'https://app-a.com/callback',
        'confidential' => '1',
    ]);

    $client = OAuthClient::where('name', 'App A (Web)')->firstOrFail();

    $response->assertRedirect(route('admin.clients.show', $client));

    expect($client->id)->not->toBeNull()
        ->and($client->confidential())->toBeTrue()
        ->and($client->hasGrantType('authorization_code'))->toBeTrue()
        ->and($client->redirect_uris)->toBe(['https://app-a.com/callback'])
        ->and($client->secret)->not->toBeNull()
        ->and(Hash::check($client->getOriginal('secret'), $client->secret))->toBeFalse();
});

test('superuser can create a client credentials client', function () {
    $superuser = createSuperuser();

    $this->actingAs($superuser)->post(route('admin.clients.store'), [
        'name' => 'App B (Service)',
        'grant' => 'client_credentials',
    ])->assertRedirect();

    $client = OAuthClient::where('name', 'App B (Service)')->firstOrFail();

    expect($client->hasGrantType('client_credentials'))->toBeTrue()
        ->and($client->redirect_uris)->toBe([]);
});

test('creating an authorization code client requires a redirect uri', function () {
    $superuser = createSuperuser();

    $this->actingAs($superuser)
        ->post(route('admin.clients.store'), [
            'name' => 'Broken',
            'grant' => 'authorization_code',
        ])
        ->assertSessionHasErrors('redirect');

    $this->actingAs($superuser)
        ->post(route('admin.clients.store'), [
            'name' => 'Broken',
            'grant' => 'authorization_code',
            'redirect' => 'not-a-url',
        ])
        ->assertSessionHasErrors('redirect');
});

test('superuser can edit the client name and redirect uri', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('Old Name', ['https://old.test/callback']);

    $this->actingAs($superuser)
        ->put(route('admin.clients.update', $client), [
            'name' => 'New Name',
            'redirect' => 'https://new.test/callback',
        ])
        ->assertRedirect(route('admin.clients.edit', $client));

    $client->refresh();

    expect($client->name)->toBe('New Name')
        ->and($client->redirect_uris)->toBe(['https://new.test/callback']);
});

test('rotating the secret requires a recent password confirmation', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient();

    $this->actingAs($superuser)
        ->post(route('admin.clients.rotate', $client))
        ->assertRedirect(route('password.confirm'));
});

test('rotating the secret invalidates the old secret and never stores it in the audit log', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('Rotate App');
    $oldSecret = $client->secret;

    $this->actingAs($superuser)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->post(route('admin.clients.rotate', $client))
        ->assertRedirect(route('admin.clients.show', $client));

    $client->refresh();

    expect($client->secret)->not->toBe($oldSecret);

    $rotateAudit = Audit::query()
        ->where('auditable_type', OAuthClient::class)
        ->where('event', 'rotate')
        ->latest('id')
        ->firstOrFail();

    expect($rotateAudit->new_values)->not->toHaveKey('secret');
});

test('the revoked client no longer issues tokens', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient();

    $this->actingAs($superuser)
        ->patch(route('admin.clients.toggle', $client))
        ->assertRedirect(route('admin.clients.index'));

    expect($client->refresh()->revoked)->toBeTrue()
        ->and(app(ClientRepository::class)->findActive($client->id))->toBeNull();
});

test('re-enabling a revoked client makes it active again', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient();
    $client->forceFill(['revoked' => true])->save();

    $this->actingAs($superuser)
        ->patch(route('admin.clients.toggle', $client));

    expect($client->refresh()->revoked)->toBeFalse()
        ->and(app(ClientRepository::class)->findActive($client->id))->not->toBeNull();
});

test('deleting a client revokes all of its access and refresh tokens', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('Doomed App');
    $user = User::factory()->create();

    $token = Token::query()->create([
        'id' => 't'.str_repeat('b', 39),
        'user_id' => $user->id,
        'client_id' => $client->id,
        'scopes' => '[]',
        'revoked' => false,
        'expires_at' => now()->addDay(),
    ]);

    $refreshToken = RefreshToken::query()->create([
        'id' => 'r'.str_repeat('c', 39),
        'access_token_id' => $token->id,
        'revoked' => false,
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($superuser)
        ->delete(route('admin.clients.destroy', $client))
        ->assertRedirect(route('admin.clients.index'));

    expect($token->refresh()->revoked)->toBeTrue()
        ->and($refreshToken->refresh()->revoked)->toBeTrue()
        ->and($client->refresh()->revoked)->toBeTrue();
});

test('client management actions are recorded in the audit log without leaking the secret', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('Audited App');

    $this->actingAs($superuser)->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->post(route('admin.clients.rotate', $client));

    $this->actingAs($superuser)->patch(route('admin.clients.toggle', $client));

    $client->refresh();

    expect(Audit::query()->where('auditable_type', OAuthClient::class)->where('event', 'created')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', OAuthClient::class)->where('event', 'rotate')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', OAuthClient::class)->where('event', 'toggle')->count())->toBeGreaterThanOrEqual(1);

    Audit::query()->where('auditable_type', OAuthClient::class)->get()->each(function ($audit): void {
        expect($audit->new_values)->not->toHaveKey('secret')
            ->and($audit->old_values)->not->toHaveKey('secret');
    });
});

test('the client show page reveals the rotated secret exactly once', function () {
    $superuser = createSuperuser();
    $client = createOAuthClient('Reveal App');

    $this->actingAs($superuser)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->post(route('admin.clients.rotate', $client));

    $this->actingAs($superuser)
        ->get(route('admin.clients.show', $client))
        ->assertOk()
        ->assertSee('New client secret');

    $this->actingAs($superuser)
        ->get(route('admin.clients.show', $client))
        ->assertOk()
        ->assertDontSee('New client secret');
});
