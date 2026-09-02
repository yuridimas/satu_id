---
paths:
  - phpunit.xml
  - '**/*.php'
---

# General

## Tests use PostgreSQL test DB (not SQLite)
Satu ID uses PostgreSQL exclusively. Test suite runs against its own `satu_id_4_test` DB (phpunit.xml sets pgsql + DB_DATABASE=satu_id_4_test). Do not revert to sqlite `:memory:`; a separate Postgres DB is required (mysql client default in .env).

## Keep APP_LOCALE=en in phpunit.xml
phpunit.xml pins APP_LOCALE=en because lang assertions (e.g. SecurityTest) match English strings, while the app default locale is `id`. Do not remove APP_LOCALE from phpunit.xml or tests will fail.

## Log out before force-deleting an authenticated user
Before calling forceDelete() on an authenticated User, log the user out FIRST with Auth::guard('web')->logout(). SessionGuard::logout() cycles the remember token by calling $user->save(); after forceDelete() the model's `exists` is false, so save() performs an INSERT that silently resurrects the deleted row (same id). This bit the settings account-delete SFC.
