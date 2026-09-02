<?php

use App\Models\Audit;
use App\Models\User;

test('a disabled user cannot sign in', function () {
    $user = User::factory()->create(['active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors();

    $this->assertGuest();
});

test('a soft deleted user cannot sign in', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors();

    $this->assertGuest();
});

test('successful sign ins, failures and logouts are audited', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors();

    expect(Audit::query()->where('auditable_type', User::class)->where('event', 'login')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', User::class)->where('event', 'logout')->count())->toBeGreaterThanOrEqual(1)
        ->and(Audit::query()->where('auditable_type', User::class)->where('event', 'failed')->count())->toBeGreaterThanOrEqual(1);
});
