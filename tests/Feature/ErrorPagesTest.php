<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    config(['app.debug' => false]);

    Route::middleware('web')->get('/__tests/errors/{status}', function (int $status): never {
        abort($status, 'sensitive-exception-detail-xyz');
    });
});

dataset('http error pages', [
    'unauthorized' => [401, 'Unauthorized'],
    'payment required' => [402, 'Payment Required'],
    'forbidden' => [403, 'Forbidden'],
    'not found' => [404, 'Not Found'],
    'page expired' => [419, 'Page Expired'],
    'too many requests' => [429, 'Too Many Requests'],
    'server error' => [500, 'Server Error'],
    'service unavailable' => [503, 'Service Unavailable'],
]);

test('HTTP error pages show safe localized guidance and a home link', function (int $status, string $heading, string $locale) {
    app()->setLocale($locale);

    $this->withSession(['locale' => $locale])->get('/__tests/errors/'.$status)
        ->assertStatus($status)
        ->assertSee('<html lang="'.$locale.'">', false)
        ->assertSeeText((string) $status)
        ->assertSeeText(__($heading))
        ->assertSeeText(__('errors.http.'.$status))
        ->assertDontSeeText('errors.http.')
        ->assertSeeText('SatuID')
        ->assertSee('href="'.route('home').'"', false)
        ->assertSeeText(__('Go Home'))
        ->assertDontSee('sensitive-exception-detail-xyz')
        ->assertDontSee('data-test="theme-toggle"', false);
})->with('http error pages')->with(['en', 'id']);

test('HTTP error pages use their heading when the exception message is empty', function (int $status, string $heading) {
    Route::get('/__tests/empty-error', fn () => abort($status));

    $this->get('/__tests/empty-error')
        ->assertStatus($status)
        ->assertSeeText($heading)
        ->assertSeeText(__('errors.http.'.$status));
})->with('http error pages');

test('HTTP error templates render without an exception variable', function (int $status, string $heading) {
    View::addNamespace('errors', resource_path('views/errors'));

    $this->view('errors.'.$status)
        ->assertSeeText($heading)
        ->assertSeeText(__('errors.http.'.$status))
        ->assertSeeText('SatuID');
})->with('http error pages');

test('HTTP error templates accept a null exception', function () {
    View::addNamespace('errors', resource_path('views/errors'));

    $this->view('errors.404', ['exception' => null])
        ->assertSeeText('Not Found')
        ->assertSeeText(__('errors.http.404'));
});

test('an unmatched URL shows helpful translated not found copy', function (string $locale, string $description) {
    app()->setLocale($locale);

    $this->get('/non-existent-page-url-xyz')
        ->assertNotFound()
        ->assertSeeText(__('Not Found'))
        ->assertSeeText($description)
        ->assertSee('href="'.route('home').'"', false);
})->with([
    ['en', 'We could not find this page. Check the address or return to the home page.'],
    ['id', 'Halaman ini tidak ditemukan. Periksa alamat atau kembali ke halaman utama.'],
]);

test('authentication and expired session errors offer a normal login link', function (int $status) {
    $this->get('/__tests/errors/'.$status)
        ->assertStatus($status)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSeeText(__('Log in'))
        ->assertDontSee('wire:navigate', false)
        ->assertDontSee('<form', false);
})->with([401, 419]);

test('unexpected exceptions show a safe server error page with debug disabled', function (string $locale) {
    app()->setLocale($locale);
    Route::get('/__tests/unexpected-error', function (): never {
        throw new RuntimeException('sensitive-server-detail-xyz');
    });

    $this->get('/__tests/unexpected-error')
        ->assertInternalServerError()
        ->assertSeeText(__('Server Error'))
        ->assertSeeText(__('errors.http.500'))
        ->assertSee('href="'.route('home').'"', false)
        ->assertDontSee('sensitive-server-detail-xyz');
})->with(['en', 'id']);

test('error pages load the existing appearance default and assets', function () {
    $this->get('/__tests/errors/404')
        ->assertNotFound()
        ->assertSee('flux.appearance', false)
        ->assertSee('id="theme-default"', false)
        ->assertDontSee('<html lang="en" class="dark">', false);
});

test('JSON error requests retain the existing response contract', function () {
    $this->getJson('/__tests/errors/403')
        ->assertForbidden()
        ->assertExactJson(['message' => 'sensitive-exception-detail-xyz']);
});

test('signed-in users receive the branded error page', function (int $status) {
    $this->actingAs(User::factory()->make());

    $this->get('/__tests/errors/'.$status)
        ->assertStatus($status)
        ->assertSeeText('SatuID')
        ->assertSeeText(__('errors.http.'.$status))
        ->assertSee('href="'.route('home').'"', false)
        ->assertDontSee('sensitive-exception-detail-xyz');
})->with([403, 404, 500]);

test('error templates render without consulting authentication', function () {
    View::addNamespace('errors', resource_path('views/errors'));
    Auth::shouldReceive('check')->never();

    $this->view('errors.500')
        ->assertSeeText('Server Error')
        ->assertSeeText(__('errors.http.500'));
});
