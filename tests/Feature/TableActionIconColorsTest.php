<?php

use App\Models\ExportHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('user row actions use semantic colors for both enabled states', function () {
    $superuser = createSuperuser();
    User::factory()->create(['active' => true]);
    User::factory()->create(['active' => false]);

    Livewire::actingAs($superuser)->test('pages::users.index')
        ->assertSee('text-slate-600')
        ->assertSee('dark:text-slate-400')
        ->assertSee('text-blue-600')
        ->assertSee('dark:text-blue-400')
        ->assertSee('text-emerald-600')
        ->assertSee('dark:text-emerald-400')
        ->assertSee('text-amber-600')
        ->assertSee('dark:text-amber-400')
        ->assertSee('text-red-600')
        ->assertSee('dark:text-red-400');
});

test('client row actions use semantic colors for both active states', function () {
    $superuser = createSuperuser();
    createOAuthClient('Active Client');
    $revokedClient = createOAuthClient('Revoked Client');
    $revokedClient->update(['revoked' => true]);

    Livewire::actingAs($superuser)->test('pages::clients.index')
        ->assertSee('text-slate-600')
        ->assertSee('dark:text-slate-400')
        ->assertSee('text-blue-600')
        ->assertSee('dark:text-blue-400')
        ->assertSee('text-emerald-600')
        ->assertSee('dark:text-emerald-400')
        ->assertSee('text-amber-600')
        ->assertSee('dark:text-amber-400')
        ->assertSee('text-red-600')
        ->assertSee('dark:text-red-400');
});

test('trashed user row actions use view and restore colors', function () {
    $superuser = createSuperuser();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    Livewire::actingAs($superuser)->test('pages::users.trash')
        ->assertSee('text-slate-600')
        ->assertSee('dark:text-slate-400')
        ->assertSee('text-emerald-600')
        ->assertSee('dark:text-emerald-400');
});

test('completed export history download uses violet colors', function () {
    Storage::fake('exports');

    $superuser = createSuperuser();
    ExportHistory::create([
        'type' => 'users',
        'file' => 'users.xlsx',
        'row_count' => 1,
        'progress' => 100,
        'status' => 'completed',
        'user_id' => $superuser->id,
    ]);

    Livewire::actingAs($superuser)->test('pages::exports.show', ['type' => 'users'])
        ->assertSee('text-violet-600')
        ->assertSee('dark:text-violet-400');
});
