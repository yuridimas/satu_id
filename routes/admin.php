<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'superuser'])->group(function (): void {
    Route::bind('user', fn (string $value): User => User::withTrashed()->findOrFail($value));

    Route::livewire('users', 'pages::users.index')->name('users.index');
    Route::livewire('users/create', 'pages::users.create')->name('users.create');
    Route::livewire('users/trash', 'pages::users.trash')->name('users.trash');
    Route::livewire('users/{user}', 'pages::users.show')->name('users.show');
    Route::livewire('users/{user}/edit', 'pages::users.edit')->name('users.edit');

    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::livewire('clients', 'pages::clients.index')->name('clients.index');
    Route::livewire('clients/create', 'pages::clients.create')->name('clients.create');
    Route::livewire('clients/{client}', 'pages::clients.show')->name('clients.show');
    Route::livewire('clients/{client}/edit', 'pages::clients.edit')->name('clients.edit');

    Route::livewire('audits', 'pages::audits.index')->name('audits.index');

    Route::livewire('exports/{type}', 'pages::exports.show')->whereIn('type', ['users', 'clients', 'audits'])->name('exports.show');
    Route::get('exports/history/{history}/download', [ExportController::class, 'download'])->name('exports.history.download');

    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::post('clients/{client}/rotate-secret', [ClientController::class, 'rotate'])
        ->middleware('password.confirm')
        ->name('clients.rotate');
    Route::patch('clients/{client}/toggle', [ClientController::class, 'toggle'])->name('clients.toggle');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});
