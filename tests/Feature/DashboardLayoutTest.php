<?php

use App\Models\User;

test('authenticated user sees top navbar and ambient layout canvas', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-flux-header', false)
        ->assertSee('bg-ambient-canvas', false);
});
