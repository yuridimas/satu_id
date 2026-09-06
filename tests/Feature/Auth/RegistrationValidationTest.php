<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration rejects a duplicate email address', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('registration rejects a malformed email address', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest();
});
