<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

test('guests cannot access the pulse dashboard', function () {
    $this->get(route('pulse'))
        ->assertForbidden();
});

test('regular users cannot access the pulse dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pulse'))
        ->assertForbidden();
});

test('superusers can access the pulse dashboard', function () {
    $superuser = createSuperuser();

    $this->actingAs($superuser)
        ->get(route('pulse'))
        ->assertOk();
});

test('monitoring nav item opens pulse in a new tab', function () {
    $superuser = createSuperuser();

    // Only the Monitoring item uses target="_blank" in the sidebar.
    $this->actingAs($superuser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('target="_blank"', false);
});

test('cached objects keep their classes when read back', function () {
    // Pulse cards cache Collections of stdClass via Cache::flexible() on the
    // database store. With a blanket serializable_classes deny, the second
    // read returns incomplete objects and dashboard cards crash calling
    // methods on them. NOTE: the database store is used explicitly because
    // phpunit.xml sets CACHE_STORE=array, which skips serialization entirely.
    $store = Cache::store('database');
    $key = 'regression:cached-objects';

    $first = $store->flexible($key, [60, 120], fn () => collect([(object) ['a' => 1]]));
    $second = $store->flexible($key, [60, 120], fn () => collect([(object) ['should-not-run' => true]]));

    expect($first)->toBeInstanceOf(Collection::class)
        ->and($second)->toBeInstanceOf(Collection::class)
        ->and($second->first())->toBeInstanceOf(stdClass::class)
        ->and((array) $second->first())->toBe(['a' => 1]);
});
