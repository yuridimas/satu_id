<?php

use App\Models\Audit;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from the audit logs page', function () {
    $this->get(route('admin.audits.index'))
        ->assertRedirect(route('login'));
});

test('regular users cannot access the audit logs page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.audits.index'))
        ->assertForbidden();
});

test('superuser sees recorded audits on the audit logs page', function () {
    $superuser = createSuperuser();
    User::factory()->create(['name' => 'Audit Target']);

    expect(Audit::where('event', 'created')->count())->toBeGreaterThan(0);

    $this->actingAs($superuser)
        ->get(route('admin.audits.index'))
        ->assertOk()
        ->assertSee('created');
});

test('superuser can filter audits by search and event', function () {
    $superuser = createSuperuser();
    User::factory()->create();

    Livewire::actingAs($superuser)->test('pages::audits.index')
        ->set('searchDraft', 'created')
        ->call('applyFilters')
        ->assertSee('created');

    Livewire::actingAs($superuser)->test('pages::audits.index')
        ->set('eventDraft', 'created')
        ->call('applyFilters')
        ->assertSee('created');
});
