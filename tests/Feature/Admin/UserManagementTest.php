<?php

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;

test('regular users cannot access any admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.clients.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.users.store'), ['name' => 'Hijack', 'email' => 'hijack@test.dev', 'password' => 'password'])
        ->assertForbidden();
});

test('superuser can view the users index containing only regular users', function () {
    $superuser = createSuperuser();
    $regular = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@mail.test']);

    $this->actingAs($superuser)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($regular->name);
});

test('superuser can create a regular user with a hashed password', function () {
    $superuser = createSuperuser();

    $response = $this->actingAs($superuser)->post(route('admin.users.store'), [
        'name' => 'Siti Aminah',
        'email' => 'siti@mail.test',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $created = User::where('email', 'siti@mail.test')->firstOrFail();

    expect($created->role)->toBe(UserRole::User)
        ->and($created->active)->toBeTrue()
        ->and(Hash::check('secret-password', $created->password))->toBeTrue()
        ->and($created->getAttribute('password'))->not->toBe('secret-password');
});

test('creating a user rejects duplicate email addresses', function () {
    $superuser = createSuperuser();
    User::factory()->create(['email' => 'taken@mail.test']);

    $this->actingAs($superuser)
        ->post(route('admin.users.store'), [
            'name' => 'Dup',
            'email' => 'taken@mail.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
        ->assertSessionHasErrors('email');
});

test('superuser can edit name and email but never the role', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@mail.test']);

    $this->actingAs($superuser)
        ->put(route('admin.users.update', $user), [
            'name' => 'New Name',
            'email' => 'new@mail.test',
        ])
        ->assertRedirect(route('admin.users.edit', $user));

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@mail.test')
        ->and($user->role)->toBe(UserRole::User);
});

test('disabling a user updates the active flag', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create();

    $this->actingAs($superuser)
        ->patch(route('admin.users.toggle', $user))
        ->assertRedirect(route('admin.users.index'));

    expect($user->refresh()->active)->toBeFalse();
});

test('deleting a user soft deletes the record and revokes their tokens', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create();

    $token = Token::query()->create([
        'id' => str_repeat('a', 40),
        'user_id' => $user->id,
        'client_id' => createOAuthClient()->id,
        'scopes' => '[]',
        'revoked' => false,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($superuser)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and($token->refresh()->revoked)->toBeTrue();
});

test('a soft deleted user appears on the trash page', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create(['name' => 'Rina Putri']);

    $user->delete();

    $this->actingAs($superuser)
        ->get(route('admin.users.trash'))
        ->assertOk()
        ->assertSee('Rina Putri');
});

test('restoring a user brings them back to the active list', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($superuser)
        ->post(route('admin.users.restore', $user))
        ->assertRedirect(route('admin.users.trash'));

    expect(User::find($user->id))->not->toBeNull();
});

test('superusers cannot manage other superuser accounts', function () {
    $superuser = createSuperuser();
    $otherSuperuser = User::factory()->create(['role' => UserRole::Superuser]);

    $this->actingAs($superuser)
        ->get(route('admin.users.show', $otherSuperuser))
        ->assertForbidden();

    $this->actingAs($superuser)
        ->patch(route('admin.users.toggle', $otherSuperuser))
        ->assertForbidden();

    $this->actingAs($superuser)
        ->delete(route('admin.users.destroy', $otherSuperuser))
        ->assertForbidden();
});

test('user management actions are recorded in the audit log', function () {
    $superuser = createSuperuser();
    $user = User::factory()->create();

    $this->actingAs($superuser)->post(route('admin.users.store'), [
        'name' => 'Audited User',
        'email' => 'audited@mail.test',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $this->actingAs($superuser)->patch(route('admin.users.toggle', $user));

    $user->delete();

    $this->actingAs($superuser)->post(route('admin.users.restore', $user));

    expect(Audit::query()->where('auditable_type', User::class)->where('event', 'created')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', User::class)->where('event', 'updated')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', User::class)->where('event', 'deleted')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', User::class)->where('event', 'restored')->count())->toBeGreaterThanOrEqual(1);
});

test('the users index livewire component lists users and respects the role filter', function () {
    $superuser = createSuperuser();
    $regular = User::factory()->create(['name' => 'Listed User']);

    $component = Livewire::actingAs($superuser)->test('pages::users.index');

    expect($component->users->pluck('id'))->toContain($regular->id)
        ->and($component->users->pluck('id'))->not->toContain($superuser->id);
});
