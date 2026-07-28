#!/bin/sh
set -e

# Код «запечён» в образ по пути /app. При старте копируем его в общий
# volume /var/www (его же монтирует nginx), сохраняя смонтированные с хоста
# storage и .env.
#
# Вендор сносим перед синхронизацией: rsync сверяет файлы по размеру и mtime,
# а composer при каждой установке пишет в autoload.php и composer/autoload_real.php
# имя класса вида ComposerAutoloaderInit<32 hex> — длина не меняется, поэтому
# rsync считает файлы одинаковыми и оставляет половину вендора от прошлой сборки.
# Итог — «Class ComposerAutoloaderInit... not found» на любом artisan.
rm -rf /var/www/vendor

rsync -a --delete \
  --exclude '/storage' \
  --exclude '/.env' \
  /app/ /var/www/

# storage монтируется с хоста и может быть пустым — создаём стандартную
# структуру Laravel, иначе artisan падает с "Please provide a valid cache path".
mkdir -p \
  /var/www/storage/framework/cache/data \
  /var/www/storage/framework/sessions \
  /var/www/storage/framework/views \
  /var/www/storage/logs \
  /var/www/storage/app/public

# Ключ сервис-аккаунта Firebase (FIREBASE_CREDENTIALS) лежит под storage/,
# который исключён из rsync выше, поэтому в том сам по себе не попадает —
# без него FCM падает с "Invalid service account: file does not exist".
# Копируем из образа только если в томе ключа ещё нет: положенный вручную
# (например, боевой) файл не перезатираем.
if [ -d /app/storage/app/firebase ]; then
  mkdir -p /var/www/storage/app/firebase
  for src in /app/storage/app/firebase/*.json; do
    [ -e "$src" ] || continue
    dst="/var/www/storage/app/firebase/$(basename "$src")"
    [ -e "$dst" ] || cp "$src" "$dst"
  done
fi

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

exec "$@"
