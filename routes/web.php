<?php

use App\Http\Controllers\AuthorizedAppsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('authorized-apps', 'pages::authorized-apps.index')->name('user.authorized-apps');

    Route::post('authorized-apps/{token}/revoke', [AuthorizedAppsController::class, 'revoke'])
        ->name('user.authorized-apps.revoke');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
