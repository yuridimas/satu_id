#!/bin/sh
# Entrypoint service PHP (app/queue/scheduler/migrate).
#
# SENGAJA TIDAK menjalankan `php artisan migrate` di sini: migration
# dipegang service `migrate` (run-once) supaya start bersamaan tidak
# race condition. Script ini hanya: tunggu DB siap + pastikan Passport
# keys ada (sekali, tersimpan di volume persisten `app-storage`).
set -e

echo "Waiting for database (${DB_HOST}:${DB_PORT})..."

until php -r "try { new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Throwable) { exit(1); }"; do
    sleep 2
done

echo "Database is ready."

# Passport keys: generate HANYA bila belum ada (file maupun env).
# Key yang berubah meng-invalid-kan semua token user yang sudah terbit.
if [ -z "$PASSPORT_PRIVATE_KEY" ] && [ ! -f storage/oauth-private.key ]; then
    echo "Generating Passport encryption keys (persisted in app-storage volume)..."
    php artisan passport:keys --no-interaction
fi

exec "$@"
