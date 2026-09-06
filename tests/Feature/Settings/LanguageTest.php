<?php

use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from the language settings page', function () {
    $this->get(route('language.edit'))
        ->assertRedirect(route('login'));
});

test('verified users can view the language settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('language.edit'))
        ->assertOk();
});

it('stores the selected locale in the session', function (string $locale) {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('pages::settings.language')
        ->set('locale', $locale)
        ->assertHasNoErrors()
        ->assertSessionHas('locale', $locale);

    expect(session('locale'))->toBe($locale);
})->with(['en', 'id']);

it('ignores an unsupported locale', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('pages::settings.language')
        ->set('locale', 'xx')
        ->assertHasNoErrors()
        ->assertSessionMissing('locale');
});
