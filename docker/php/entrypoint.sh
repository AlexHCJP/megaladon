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
#
# Удаляем только если в образе есть чем заменить: иначе снос рабочего вендора
# оставит том пустым и artisan будет падать на require autoload.php.
if [ -f /app/vendor/autoload.php ]; then
  rm -rf /var/www/vendor
else
  echo "entrypoint: в образе нет /app/vendor/autoload.php — composer install не отработал при сборке" >&2
  echo "entrypoint: оставляю /var/www/vendor как есть, приложение может не запуститься" >&2
fi

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

# Без автозагрузчика не работает ни php-fpm, ни artisan. Падаем здесь с внятным
# текстом, а не PHP-фаталом «Failed opening required vendor/autoload.php»
# посреди миграций в логе деплоя.
if [ ! -f /var/www/vendor/autoload.php ]; then
  echo "entrypoint: /var/www/vendor/autoload.php отсутствует после синхронизации — образ собран без зависимостей" >&2
  exit 1
fi

exec "$@"
