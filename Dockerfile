# SatuID — multi-stage build (production-ready, dipakai semua service PHP di compose)
#
# Stage node .... : compile Vite + Tailwind v4 (Flux UI resolve via Blade, bukan plugin Tailwind)
# Stage composer . : install PHP deps production only (tanpa Pest/dev tools)
# Stage app ...... : PHP-FPM 8.5 (parity dengan runtime lokal 8.5.x & CI 8.5), non-root www-data

# ------------------------------------------------------------ stage: composer
# DULUAN: app.css meng-import vendor/livewire/flux + pagination views,
# jadi stage node butuh vendor dari stage ini (COPY --from=composer).
FROM composer:2 AS composer

WORKDIR /app

# Image composer:2 (Alpine) tidak membawa ext-bcmath & ext-gd yang
# dibutuhkan lock file (laravel-lang via dragon-code/support,
# maatwebsite/excel via phpspreadsheet) — install dulu agar platform check lolos.
RUN apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd

# Copy source DULU: script post-autoload-dump (package:discover) butuh artisan.
# (.dockerignore menjaga vendor/.env/node_modules tidak ikut masuk.)
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# ---------------------------------------------------------------- stage: node
FROM node:22-bookworm-slim AS node

WORKDIR /app

COPY package.json pnpm-lock.yaml ./

RUN corepack enable pnpm && pnpm install --frozen-lockfile

COPY . .

# CSS mengacu ke file di vendor/ (flux dist, pagination views) — bukan dari npm.
COPY --from=composer /app/vendor ./vendor

RUN pnpm run build

# ----------------------------------------------------------------- stage: app
FROM php:8.5-fpm-bookworm AS app

# Extension yang benar-benar dipakai project:
# pdo_pgsql/pgsql (DB pgsql), zip/xml (maatwebsite/excel), gd (spreadsheet),
# bcmath/pcntl (queue worker), exif, opcache (prod).
# NOTE: opcache TIDAK di-install manual — di image php:8.5 ia sudah ter-compile
# bawaan (docker-php-ext-install opcache justru gagal). Tuning via docker/php/local.ini.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql pgsql zip bcmath pcntl exif gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

# Source dulu (tanpa vendor/node_modules/.env berkat .dockerignore),
# lalu timpa dengan hasil dua stage build di atas.
COPY . .

COPY --from=composer /app/vendor ./vendor

COPY --from=node /app/public/build ./public/build

RUN chmod +x docker/entrypoint.sh \
    && mkdir -p storage/app/private/exports storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Non-root: image resmi PHP sudah menyediakan user khusus www-data,
# sama dengan user yang dipakai pool php-fpm secara default.
USER www-data

EXPOSE 9000

ENTRYPOINT ["docker/entrypoint.sh"]

CMD ["php-fpm"]
