<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

function translationKeyExistsForLocale(string $locale, string $key, array $jsonCatalog): bool
{
    if (array_key_exists($key, $jsonCatalog)) {
        return true;
    }

    $segments = explode('.', $key);
    $file = array_shift($segments);
    $path = lang_path("{$locale}/{$file}.php");

    if (! is_file($path)) {
        return false;
    }

    $translation = require $path;

    foreach ($segments as $segment) {
        if (! is_array($translation) || ! array_key_exists($segment, $translation)) {
            return false;
        }

        $translation = $translation[$segment];
    }

    return is_string($translation);
}

test('supported JSON translation catalogs have identical keys', function () {
    $english = json_decode(File::get(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
    $indonesian = json_decode(File::get(lang_path('id.json')), true, flags: JSON_THROW_ON_ERROR);

    expect(array_keys($indonesian))->toEqualCanonicalizing(array_keys($english));
});

test('supported PHP translation catalogs have matching keys and localized values', function () {
    $sharedTerms = ['Admin', 'filter', 'slug', 'status', 'tag'];

    foreach (['actions', 'auth', 'errors', 'http-statuses', 'pagination', 'passwords', 'validation'] as $file) {
        $english = require lang_path("en/{$file}.php");
        $indonesian = require lang_path("id/{$file}.php");
        $compare = function (array $source, array $translation, string $path = '') use (&$compare, $sharedTerms): void {
            expect(array_keys($translation))->toEqualCanonicalizing(array_keys($source));

            foreach ($source as $key => $value) {
                $currentPath = $path === '' ? (string) $key : "{$path}.{$key}";
                $translated = $translation[$key];

                if (is_array($value) && is_array($translated)) {
                    $compare($value, $translated, $currentPath);

                    continue;
                }

                if (is_string($value) && is_string($translated) && $value === $translated) {
                    expect($value)->toBeIn($sharedTerms);
                }
            }
        };

        $compare($english, $indonesian);
    }
});

test('every literal translation call and page title is registered for each supported locale', function () {
    $jsonCatalogs = [];

    foreach (['en', 'id'] as $locale) {
        $jsonCatalogs[$locale] = json_decode(File::get(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
    }

    $roots = ['app', 'resources/views', 'resources/js', 'routes'];
    $patterns = [
        <<<'REGEX'
~__\(\s*'((?:\\.|[^'\\])*)'\s*(?:,|\))~s
REGEX,
        <<<'REGEX'
~__\(\s*"((?:\\.|[^"\\])*)"\s*(?:,|\))~s
REGEX,
        <<<'REGEX'
~#\[Title\(\s*'((?:\\.|[^'\\])*)'\s*\)\]~s
REGEX,
        <<<'REGEX'
~#\[Title\(\s*"((?:\\.|[^"\\])*)"\s*\)\]~s
REGEX,
    ];
    $missingTranslations = [];
    $usedTranslationKeys = [];

    foreach ($roots as $root) {
        foreach (File::allFiles(base_path($root)) as $file) {
            if (str_starts_with($file->getPathname(), resource_path('views/flux'))) {
                continue;
            }

            $source = File::get($file->getPathname());

            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $source, $matches);

                foreach ($matches[1] as $key) {
                    $key = stripcslashes($key);
                    $usedTranslationKeys[$key] = true;

                    foreach (['en', 'id'] as $locale) {
                        if (! translationKeyExistsForLocale($locale, $key, $jsonCatalogs[$locale])) {
                            $missingTranslations[$locale][$file->getRelativePathname()][] = $key;
                        }
                    }
                }
            }
        }
    }

    expect($missingTranslations)->toBeEmpty();

    $sharedTerms = ['ID', 'IP', 'OAuth', 'OAuth2', 'SSO', 'WebAuthn', 'XLSX', 'Status', 'Authorization Code', 'Client Credentials', 'Superuser', 'Platform', 'Total', 'Refresh Token', 'Personal Access', '—', 'DB'];
    $untranslatedIndonesian = array_filter(
        array_keys($usedTranslationKeys),
        fn (string $key) => array_key_exists($key, $jsonCatalogs['id'])
            && $jsonCatalogs['id'][$key] === $key
            && ! in_array($key, $sharedTerms, true),
    );

    expect($untranslatedIndonesian)->toBeEmpty();
});

test('dashboard title and copy follow the selected locale', function (string $locale, string $title, string $summary) {
    $user = User::factory()->create(['role' => 'superuser']);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText($title.' - '.config('app.name'))
        ->assertSeeText($summary);
})->with([
    'English' => ['en', 'Dashboard', 'System health'],
    'Indonesian' => ['id', 'Dasbor', 'Kesehatan sistem'],
]);
test('Indonesian catalog translates every phrase except shared technical terms', function () {
    $indonesian = json_decode(File::get(lang_path('id.json')), true, flags: JSON_THROW_ON_ERROR);
    $sharedTerms = ['ID', 'IP', 'OAuth', 'OAuth2', 'SSO', 'WebAuthn', 'XLSX', 'Status', 'Authorization Code', 'Client Credentials', 'Superuser', 'Platform', 'Total', 'Refresh Token', 'Personal Access', '—', 'DB'];
    $untranslated = array_filter(
        $indonesian,
        fn (string $translation, string $source) => $translation === $source && ! in_array($source, $sharedTerms, true),
        ARRAY_FILTER_USE_BOTH,
    );

    expect($untranslated)->toBeEmpty();
});
