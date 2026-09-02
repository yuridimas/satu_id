<?php

use App\Models\ExportHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('superuser can export users xlsx and see progress and history', function () {
    Storage::fake('exports');
    $superuser = User::factory()->create(['role' => 'superuser']);
    User::factory()->count(3)->create(['role' => 'user']);

    Livewire::actingAs($superuser)->test('pages::exports.show', ['type' => 'users'])
        ->assertSee('Akan mengekspor')
        ->call('startExport')
        ->assertHasNoErrors();

    expect(ExportHistory::where('type', 'users')->count())->toBe(1);

    $history = ExportHistory::first();
    expect($history->status)->toBe('completed');
    expect($history->progress)->toBe(100);
    Storage::disk('exports')->assertExists($history->file);
});

test('audit export requires password', function () {
    $superuser = User::factory()->create(['role' => 'superuser']);
    Livewire::actingAs($superuser)->test('pages::exports.show', ['type' => 'audits'])
        ->call('startExport')
        ->assertHasErrors(['password']);
});

test('regular user cannot access export page', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user)->get(route('admin.exports.show', 'users'))->assertForbidden();
});

test('download without limit while history exists', function () {
    Storage::fake('exports');
    $superuser = User::factory()->create(['role' => 'superuser']);
    $file = 'test.xlsx';
    Storage::disk('exports')->put($file, 'fake-content');
    $history = ExportHistory::create([
        'type' => 'users',
        'file' => $file,
        'row_count' => 1,
        'progress' => 100,
        'status' => 'completed',
        'user_id' => $superuser->id,
    ]);

    $this->actingAs($superuser)->get(route('admin.exports.history.download', $history))->assertOk();
    // Second download should still work (no expiry)
    $this->actingAs($superuser)->get(route('admin.exports.history.download', $history))->assertOk();
});

test('prune command deletes file and row after 7 days', function () {
    Storage::fake('exports');
    $user = User::factory()->create(['role' => 'superuser']);
    $file = 'old.xlsx';
    Storage::disk('exports')->put($file, 'old');
    $history = ExportHistory::create([
        'type' => 'users',
        'file' => $file,
        'row_count' => 1,
        'progress' => 100,
        'status' => 'completed',
        'user_id' => $user->id,
    ]);

    // created_at/updated_at are not mass-assignable; simulate an old record
    ExportHistory::where('id', $history->id)->update([
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);

    $this->artisan('exports:prune')->assertExitCode(0);
    expect(ExportHistory::find($history->id))->toBeNull();
    Storage::disk('exports')->assertMissing($file);
});
