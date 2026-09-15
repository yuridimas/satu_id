# Menjalankan SatuID dengan Docker

Stack containerized untuk **local development**: `app` (PHP-FPM 8.5) +
`web` (Nginx) + `db` (Postgres 17) + `queue` + `scheduler` + `migrate`
(run-once). Tidak ada Redis — cache/session/queue project ini memakai
driver `database` (lihat `.env.example`).

## Prasyarat

- Docker Engine + Compose plugin (`docker compose version`)
- Port `8080` (web) dan `5432` (db) bebas di host

## Pertama kali

```bash
# Pakai .env yang sama seperti local (sudah ada dari composer setup).
# Isi SUPERUSER_EMAIL + SUPERUSER_PASSWORD untuk akun superuser awal.
# Hanya 2 variabel yang dioverride otomatis oleh compose: DB_HOST=db
# dan APP_URL=http://localhost:8080 (lihat environment: di compose).

docker compose up --build -d
```

Tunggu Postgres healthy, lalu jalankan migration manual sekali via `app`:

```bash
docker compose exec app php artisan migrate --force
```

Cek:

```bash
docker compose ps
docker compose logs -f app
```

Buka `http://localhost:8080`.

## Migration susulan & rollback

Tidak ada service migrate khusus — semua lewat `exec` ke `app`:

```bash
docker compose exec app php artisan migrate --force          # jalankan pending migration
docker compose exec app php artisan migrate:status           # lihat status batch
docker compose exec app php artisan migrate:rollback --force # rollback batch terakhir
docker compose exec app php artisan migrate:fresh --force    # hapus SEMUA tabel + migrate ulang (data hilang!)
```

## Seeder (sekali saja, di volume fresh)

```bash
docker compose exec app php artisan db:seed --class=SuperuserSeeder
```

> Sengaja HANYA `SuperuserSeeder`, bukan `db:seed` penuh, karena dua alasan:
> - `DatabaseSeeder` memanggil factory (`fake()`) yang butuh
>   `fakerphp/faker` — package dev-only yang TIDAK ada di image production.
> - `DatabaseSeeder` tidak idempotent (Test User duplikat menabrak unique
>   email). `SuperuserSeeder` aman diulang (`firstOrNew`).
>
> Isi dulu `SUPERUSER_EMAIL` + `SUPERUSER_PASSWORD` di `.env`,
> recreate (`up -d --force-recreate app queue scheduler`), baru seed.
> Tanpa dua nilai itu seeder dilewati dengan peringatan.

## Operasional harian

```bash
docker compose up -d            # start
docker compose down             # stop (volume DATA TETAP ADA)

# Setiap mengubah .env: recreate agar container baca ulang
docker compose up -d --force-recreate app queue scheduler
docker compose logs -f queue    # lihat worker export XLSX
docker compose exec app bash    # masuk container untuk debug (user www-data)
docker compose exec app php artisan tinker
```

## Setelah menambah dependency composer (PENTING)

`vendor/` di container adalah named volume — rebuild image SAJA tidak
cukup, volume lama (tanpa package baru) tetap menimpa vendor baru dan
menyebabkan `Class not found`. Reset volume vendor + bootstrap-cache
(HATI-HATI: jangan sertakan `pgdata`/`app-storage`):

```bash
docker compose down
docker volume rm satu_id_4_vendor satu_id_4_bootstrap-cache
docker compose up --build -d
```

## Hot-reload frontend

Source code di-bind-mount, jadi perubahan PHP langsung terlihat. Untuk
asset Vite, jalankan di **host** (seperti workflow non-Docker):

```bash
npm run dev
```

Ubah asset yang butuh build image (`public/build`) → rebuild:

```bash
docker compose build app && docker compose up -d app queue scheduler
```

## Scheduler

Service `scheduler` menjalankan `php artisan schedule:work` (loop
foreground, bukan cron) — ini yang mengeksekusi `exports:prune` harian
dan scheduler lain di `routes/console.php`.

## Peringatan volume (penting)

- `pgdata` = seluruh database. `app-storage` = `storage/` Laravel,
  termasuk **Passport encryption keys** (`storage/oauth-private.key`,
  `oauth-public.key`).
- **Jangan** `docker compose down -v` kecuali sadar data hilang: key baru
  akan di-generate saat boot berikutnya dan **semua token OAuth user
  langsung invalid**.
- Key di-generate otomatis oleh entrypoint hanya bila belum ada (file
  maupun env `PASSPORT_PRIVATE_KEY`). Setelah key terbit, jangan isi
  ulang env key dengan nilai berbeda.
