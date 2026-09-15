# Menjalankan SatuID dengan Docker

Tiga environment, satu base (`docker-compose.yml`) + satu override per env:

| Env | Command | Ciri |
|---|---|---|
| local | `docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d` | Bind-mount source, HMR via `pnpm run dev` di container `vite` (:5173), DB publish :5432 |
| staging | `ENV_FILE=.env.staging docker compose -f docker-compose.yml -f docker-compose.staging.yml up --build -d` | Immutable (tanpa bind-mount), asset dari build image, DB publish untuk inspeksi |
| production | `ENV_FILE=.env.production docker compose -f docker-compose.yml -f docker-compose.production.yml up --build -d` | Immutable, DB tertutup, `APP_DEBUG` dipaksa mati, batas resource |

Base stack: `app` (PHP-FPM 8.5) + `web` (Nginx :8080) + `db` (Postgres 17) +
`queue` + `scheduler`. Tidak ada Redis — cache/session/queue project ini memakai
driver `database` (lihat `.env.example`).

> Contoh command di bawah memakai **local**. Untuk staging/production,
> tambahkan `-f` override + `ENV_FILE=...` yang sesuai, dan ganti `exec app`
> tetap sama (nama service identik).

## Prasyarat

- Docker Engine + Compose plugin (`docker compose version`)
- Port `8080` (web) bebas di host; local juga butuh `5432` (db) + `5173` (vite)

## Pertama kali (local)

```bash
# .env dipakai apa adanya (sudah ada dari composer setup).
# Isi SUPERUSER_EMAIL + SUPERUSER_PASSWORD untuk akun superuser awal.
# Compose local mengoverride otomatis: DB_HOST=db, APP_URL=http://localhost:8080.

docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

Service `vite` (pnpm run dev) sudah berjalan di dalam compose — HMR :5173,
file `public/hot` mengalir via bind-mount sehingga `@vite` otomatis pakai
dev server. **Jangan** jalankan dev server kedua di host (port + file `hot` berebut).

Tunggu Postgres healthy, lalu migration manual sekali via `app`:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml exec app php artisan migrate --force
```

Cek:

```bash
docker compose ps
docker compose logs -f app
```

Buka `http://localhost:8080`.

## Pertama kali (staging/production)

```bash
cp .env.staging.example .env.staging   # atau .env.production.example
# Isi: APP_KEY (php artisan key:generate --show), APP_URL, DB_PASSWORD,
# SUPERUSER_*, dan kredensial lain yang masih placeholder.

ENV_FILE=.env.staging docker compose -f docker-compose.yml -f docker-compose.staging.yml up --build -d
ENV_FILE=.env.staging docker compose -f docker-compose.yml -f docker-compose.staging.yml exec app php artisan migrate --force
```

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
> Isi dulu `SUPERUSER_EMAIL` + `SUPERUSER_PASSWORD` di env file yang dipakai,
> recreate (`up -d --force-recreate app queue scheduler`), baru seed.
> Tanpa dua nilai itu seeder dilewati dengan peringatan.

## Operasional harian

```bash
docker compose -f ... up -d            # start (sesuaikan -f per env)
docker compose -f ... down             # stop (volume DATA TETAP ADA)

# Setiap mengubah env file: recreate agar container baca ulang
docker compose -f ... up -d --force-recreate app queue scheduler
docker compose logs -f queue    # lihat worker export XLSX
docker compose exec app bash    # masuk container untuk debug (user www-data)
docker compose exec app php artisan tinker
```

## Frontend: build vs HMR per env

- **local**: service `vite` menjalankan `pnpm run dev` (HMR :5173). File `public/hot` mengalir
  via bind-mount sehingga `@vite` otomatis pakai dev server. Ubah Blade/PHP
  → terlihat langsung; ubah CSS/JS → HMR otomatis. Jangan jalankan dev
  server kedua di host (port + file `hot` berebut).
- **staging/production**: asset disajikan dari hasil build image (volume
  `app-public`, terisi sekali saat pertama dibuat). Setiap rilis frontend:
  `up --build` + `docker volume rm satu_id_4_app-public` (down dulu)
  agar bundle baru tersalin ulang.

> Jika browser console error `ERR_CONNECTION_REFUSED` ke `:5173`, penyebabnya
> file basi `public/hot` (sisa dev server yang mati tidak bersih). Hapus:
> `rm public/hot`, lalu refresh.

## Setelah menambah dependency composer (PENTING, staging/production)

`vendor/` di local adalah named volume — rebuild image SAJA tidak cukup,
volume lama (tanpa package baru) tetap menimpa vendor baru dan menyebabkan
`Class not found`. Reset volume vendor + bootstrap-cache (HATI-HATI: jangan
sertakan `pgdata`/`app-storage`/`app-public`):

```bash
docker compose -f ... down
docker volume rm satu_id_4_vendor satu_id_4_bootstrap-cache
docker compose -f ... up --build -d
```

## Setelah menambah dependency frontend (pnpm)

Tambah/ubah `package.json`, lalu rebuild stage node:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml build node
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d vite
```

Atau untuk environment non-local (staging/production), cukup `up --build`:
```bash
ENV_FILE=.env.staging docker compose -f docker-compose.yml -f docker-compose.staging.yml up --build -d
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
