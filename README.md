# SatuID

SatuID adalah **identity provider (SSO) terpusat berbasis OAuth2** — satu akun untuk login ke berbagai aplikasi/portal dalam satu instansi. Project ini dibangun dengan Laravel + Livewire dan berfungsi sebagai **portofolio/showcase pembelajaran**, bukan sistem produksi resmi instansi mana pun.

Fitur utama: registrasi & login user (Fortify, 2FA, passkey), manajemen user oleh superuser (CRUD, aktivasi/nonaktif, soft-delete + trash), manajemen OAuth2 client (create, rotate secret, revoke), halaman *authorized apps* untuk user, audit log, export data XLSX via queue, dan dashboard superuser dengan grafik aktivitas.

## Tech Stack

Diambil dari `composer.json` dan `package.json` yang terpasang:

**Backend (PHP ^8.3, Laravel ^13.17):**
- `livewire/livewire` ^4.1 (+ `livewire/blaze` ^1.0) — UI reaktif, single-file components
- `livewire/flux` ^2.13.1 (edisi free) — komponen UI
- `laravel/fortify` ^1.37.2 — autentikasi (registrasi, reset password, 2FA, passkey)
- `laravel/passport` ^13.8 — OAuth2 authorization server
- `owen-it/laravel-auditing` ^14.0 — audit log model
- `maatwebsite/excel` — export XLSX
- `laravel-lang/common` — terjemahan (locale default `id`, fallback `en`)

**Frontend:**
- `tailwindcss` ^4 + `@tailwindcss/vite` — styling
- `vite` ^8 via `vite-plus` 0.3.0 (`npm run dev` / `npm run build` menjalankan `vp`)
- `@laravel/passkeys` — WebAuthn di browser

**Dev & QA:**
- `pestphp/pest` ^5.1 — test suite
- `laravel/pint` — formatter, `larastan/larastan` — PHPStan level 7
- `laravel/boost`, `laravel/pail`, `laravel/pao`, `laravel/sail`

## Arsitektur & Konsep Kunci

**OAuth2 (Laravel Passport).** Konfigurasi token ada di `config/passport.php` (lifetime access/refresh/client-credentials dalam menit, bisa dioverride via env). Saat membuat client (`app/Actions/Clients/CreateClient.php`), grant type yang didukung hanya dua: `authorization_code` (wajib `redirect URI`) dan `client_credentials`. Tabel `oauth_device_codes` tersedia dari skema Passport, tetapi tidak ada alur device flow yang dipakai aplikasi.

**RBAC sederhana (tanpa package role).** Tidak ada tabel roles/permissions — otorisasi memakai:
- `App\Enums\UserRole`: `superuser` vs `user`, plus flag `active` dan soft deletes di model `User`.
- Middleware `superuser` (`app/Http/Middleware/Superuser.php`) menjaga seluruh grup route `admin/*`.
- `UserPolicy` / `ClientPolicy` (didaftarkan di `AppServiceProvider`) + gate `view-admin`.
- User nonaktif/dihapus ditolak login lewat listener (`BlockDisabledUsers`, `RejectDisabledUsersOnAttempt`).

**Audit log.** Setiap perubahan user/client dicatat (model `App\Models\Audit`, tabel `audits`), bisa dilihat di halaman admin Audit Logs (paginasi, filter event/tipe, search). Field sensitif (`password`, `secret`, token 2FA) dikecualikan dari log.

**Export XLSX.** Export users/clients/audits dijalankan sebagai queue job (`GenerateExportJob`) dengan progress bar, riwayat di tabel `export_histories`, dan auto-hapus file berumur > 7 hari via scheduler `exports:prune` (harian).

**Halaman utama:**
- `/dashboard` — untuk superuser: kartu kesehatan sistem, 4 KPI, grafik aktivitas 7 hari (Chart.js, seri User vs OAuth), timeline audit; untuk user biasa: sambutan + jalan pintas.
- `/admin/users`, `/admin/clients`, `/admin/audits`, `/admin/exports/{type}` — area superuser.
- `/authorized-apps` — user melihat & me-revoke token aplikasi pihak ketiga.
- `/settings/*` — profile, appearance, language (ID/EN via middleware `SetLocale`), security (2FA, passkey, ganti password).

## Requirement

- PHP ^8.3 (CI memakai PHP 8.5)
- Composer 2
- Node.js 22 (untuk build Vite)
- PostgreSQL — database dev `satu_id_4`, database test `satu_id_4_test` (bukan SQLite)

## Instalasi

```bash
composer install
cp .env .env.example
php artisan key:generate
```

Isi variabel wajib di `.env` (lihat tabel di bawah), lalu:

```bash
php artisan migrate --seed
php artisan passport:keys
npm install
npm run build
```

Atau cukup jalankan skrip setup bawaan (mencakup langkah di atas kecuali `--seed` dan `passport:keys`):

```bash
composer setup
```

Loop pengembangan normal (server + queue listener + Vite concurrently):

```bash
composer run dev
```

> Catatan: `maatwebsite/excel` didaftarkan manual (`ExcelServiceProvider`) di `bootstrap/app.php` karena auto-discovery tidak berjalan di Laravel 13 pada project ini — jangan hapus baris tersebut.

## Environment Variables Penting

Selain standar Laravel, yang spesifik project ini (lihat `.env.example`):

```bash
# Database (wajib PostgreSQL)
DB_CONNECTION=pgsql
DB_DATABASE=satu_id_4
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Akun superuser awal (dibuat oleh SuperuserSeeder via db:seed; dilewati bila kosong)
SUPERUSER_EMAIL=
SUPERUSER_NAME=Satu ID Superuser
SUPERUSER_PASSWORD=

# Passport (kosongkan untuk memakai file storage/oauth-*.key via passport:keys)
PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=
PASSPORT_TOKEN_EXPIRE=1440
PASSPORT_REFRESH_TOKEN_EXPIRE=43200
PASSPORT_CLIENT_CREDENTIALS_EXPIRE=1440

# CORS untuk endpoint token OAuth2 (daftar origin dipisah koma)
CORS_ALLOWED_ORIGINS=*
CORS_SUPPORTS_CREDENTIALS=false
```

## Struktur Folder yang Relevan

```text
app/Actions/Clients|Tokens|Users/  # operasi bisnis (Create/Update/Delete/Toggle/Rotate/Restore)
app/Enums/UserRole.php             # enum role: superuser vs user
app/Exports/                       # UsersExport, ClientsExport, AuditsExport (XLSX)
app/Http/Controllers/Admin/        # UserController, ClientController, ExportController
app/Http/Middleware/               # Superuser (guard admin), SetLocale (bahasa)
app/Jobs/GenerateExportJob.php     # export async + progress via cache
app/Listeners/                     # audit login/logout + blokir user nonaktif
app/Models/                        # User, OAuthClient (extends Passport Client), Audit, ExportHistory
app/Policies/                      # UserPolicy, ClientPolicy
app/Providers/                     # AppServiceProvider (gate, policy, Passport), EventServiceProvider, FortifyServiceProvider
app/Support/AuditLogger.php        # helper pencatatan audit manual
config/audit.php                   # konfigurasi owen-it auditing
config/passport.php                # lifetime token
config/superuser.php               # kredensial superuser dari env
config/cors.php                    # origin endpoint OAuth2
resources/views/pages/             # Livewire SFC (nama file diawali ⚡), namespace pages::
resources/views/pages/users|clients|audits|exports|authorized-apps/
routes/admin.php                   # grup admin/* (auth + verified + superuser)
routes/console.php                 # scheduler exports:prune harian
database/seeders/SuperuserSeeder.php  # akun superuser awal dari env
```

Konvensi penting: komponen Livewire berbentuk single-file (`resources/views/pages/**/⚡*.blade.php`, karakter `⚡` bagian dari nama file — jangan dihapus), direferensikan sebagai `pages::...` di route.

## Menjalankan Test

```bash
php artisan test --compact                 # seluruh suite (PostgreSQL satu_id_4_test)
php artisan test --compact --filter=<nama> # satu test
composer run test                          # pre-merge check: pint + PHPStan level 7 + tests
vendor/bin/pint --dirty                    # format file yang diubah
composer run types:check                   # PHPStan saja
```

Cakupan (saat README ditulis: 74 test / 231 assertions, Pest): manajemen user & client admin, export XLSX, authorized apps, login user nonaktif, autentikasi Fortify (registrasi, login, 2FA, reset password, verifikasi email), dan settings (profile, security).

## Menjalankan dengan Docker

Tersedia stack local-dev (`app` PHP-FPM 8.5 + Nginx + Postgres 17 +
worker queue + scheduler). Panduan lengkap ada di [DOCKER.md](DOCKER.md):

```bash
docker compose up --build -d
docker compose exec app php artisan db:seed --class=SuperuserSeeder   # sekali saja di volume fresh
```

Buka `http://localhost:8080`. Perhatian: jangan `docker compose down -v`
sembarangan — volume `app-storage` menyimpan Passport encryption keys;
key baru meng-invalid-kan semua token user yang sudah terbit.

## Catatan / Batasan

- Ini **project portofolio/pembelajaran** — jangan dipakai di sistem instansi sungguhan tanpa audit keamanan tambahan (review secret handling, rate limiting endpoint token, hardening session, dsb.).
- Tidak ada dependensi runtime ke service eksternal mana pun; semua data (user, client, audit, export) tersimpan di database PostgreSQL project ini sendiri.
- Export memakai queue driver `database` secara default — pastikan `queue:listen` berjalan (sudah termasuk dalam `composer run dev`) agar export tidak stuck di status processing.

## Catatan dari AI

Bagian konteks awal yang **tidak cocok** dengan kode aktual (README di atas mengikuti kode):

1. **DaisyUI → Flux UI.** Styling memakai komponen Flux (edisi free) + Tailwind CSS v4. Tidak ada package DaisyUI di `composer.json`/`package.json`.
2. **Tidak ada Laratrust.** Tidak ada package teams/roles/permissions, tidak ada model `Team`, tidak ada role `manage-client`/`manage-team`, dan tidak ada `RolePermissionSeeder`. RBAC aktual: enum `UserRole` + middleware `superuser` + policies (detail di bagian Arsitektur).
3. **Client tidak dimiliki Team.** Kolom polymorphic `owner_type`/`owner_id` memang ada di tabel `oauth_clients` (bawaan skema Passport), tetapi tidak ada model Team yang memakainya — client dikelola system-wide oleh superuser.
4. **Tidak ada `Gate::before` bypass.** Superuser dicek lewat middleware `superuser` pada grup `admin/*`, policy, dan gate `view-admin` — tidak ada bypass global di provider.
5. **Tidak ada `AuthServiceProvider`.** Konfigurasi Passport (model client, lifetime token) ada di `AppServiceProvider::configurePassport()`.
6. **Seeder RBAC tidak ada.** Seeder yang ada: `DatabaseSeeder` (membuat satu Test User) dan `SuperuserSeeder` (akun superuser dari env). Tidak ada alur "superadmin membuat Team & assign anggota".
7. **Tidak ada integrasi service User Management eksternal.** Tidak ada config service-account/client-credentials ke API organisasi di kode; registrasi user terbuka via Fortify (`Features::registration()`).
8. **Grant types terbatas.** Yang didukung saat pembuatan client hanya `authorization_code` dan `client_credentials` (divalidasi di `CreateClient`); tidak ada konfigurasi grant lain yang diaktifkan di provider.
9. **Build tool `vite-plus`.** `npm run dev`/`build` menjalankan `vp`, bukan `vite` langsung.
10. **Ada CI** (`.github/workflows/tests.yml`, PHP 8.5 + Node 22, menjalankan `composer ci:check`) — badge tidak dicantumkan karena URL repo publik tidak diketahui.
