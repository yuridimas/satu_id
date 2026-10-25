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

function dashboardNavigationRoutes(string $html, string $selector): array
{
    $document = new DOMDocument;
    $previousErrorHandling = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrorHandling);

    $query = match ($selector) {
        '[data-flux-navbar-items]' => '//*[@data-flux-navbar]//*[@data-flux-navbar-items]',
        '[data-flux-sidebar-item]' => '//*[@data-flux-sidebar-item]',
        '[data-flux-navbar-items][data-current]' => '//*[@data-flux-navbar]//*[@data-flux-navbar-items][@data-current]',
        default => throw new InvalidArgumentException('Unsupported navigation selector.'),
    };
    $links = (new DOMXPath($document))->query($query);

    if ($links === false) {
        return [];
    }

    $routes = [];

    foreach ($links as $link) {
        $routes[] = $link->getAttribute('href');
    }

    return $routes;
}

test('regular users see the same platform links in desktop and mobile navigation', function () {
    $user = User::factory()->create(['role' => 'user']);
    $expectedRoutes = [
        route('dashboard'),
        route('user.authorized-apps'),
    ];

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $html = $response->getContent();
    $desktopRoutes = dashboardNavigationRoutes($html, '[data-flux-navbar-items]');
    $mobileRoutes = dashboardNavigationRoutes($html, '[data-flux-sidebar-item]');

    expect($desktopRoutes)->toBe($expectedRoutes)
        ->and($mobileRoutes)->toBe($expectedRoutes)
        ->and($html)->not->toContain(route('admin.users.index'))
        ->and($html)->not->toContain(route('admin.clients.index'))
        ->and($html)->not->toContain(route('admin.audits.index'))
        ->and($html)->not->toContain(route('pulse'))
        ->and($html)->toContain(__('User'));
});

test('superusers see the same platform and administration links in desktop and mobile navigation', function () {
    $superuser = createSuperuser();
    $expectedRoutes = [
        route('dashboard'),
        route('user.authorized-apps'),
        route('admin.users.index'),
        route('admin.clients.index'),
        route('admin.audits.index'),
        route('pulse'),
    ];

    $response = $this->actingAs($superuser)->get(route('dashboard'))->assertOk();
    $html = $response->getContent();
    $desktopRoutes = dashboardNavigationRoutes($html, '[data-flux-navbar-items]');
    $mobileRoutes = dashboardNavigationRoutes($html, '[data-flux-sidebar-item]');

    expect($desktopRoutes)->toBe($expectedRoutes)
        ->and($mobileRoutes)->toBe($expectedRoutes)
        ->and($html)->toContain(__('Superuser'));
});

test('the desktop navbar marks nested admin routes as active', function () {
    $superuser = createSuperuser();

    $response = $this->actingAs($superuser)->get(route('admin.users.create'))->assertOk();
    $activeRoutes = dashboardNavigationRoutes($response->getContent(), '[data-flux-navbar-items][data-current]');

    expect($activeRoutes)->toContain(route('admin.users.index'));
});
