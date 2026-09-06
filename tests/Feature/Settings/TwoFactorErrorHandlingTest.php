<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

test('recovery codes decryption failure shows a reference id and logs a structured error', function () {
    Log::spy();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => 'corrupted-payload',
        'two_factor_confirmed_at' => now(),
    ]);

    $logged = null;

    $component = Livewire::actingAs($user)->test('pages::settings.two-factor.recovery-codes')
        ->assertHasErrors(['recoveryCodes']);

    Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use (&$logged) {
        $logged = $context;

        return true;
    });

    expect($logged['reference_id'] ?? null)->toBeUuid()
        ->and($logged['user_id'] ?? null)->toBe($user->id)
        ->and(array_keys($logged))->not->toContain('secret')
        ->and(array_keys($logged))->not->toContain('recovery_codes');

    $component->assertSee($logged['reference_id']);
});
