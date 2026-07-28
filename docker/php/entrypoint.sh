#!/bin/sh
set -e

# Маркер готовности: по нему healthcheck в docker-compose.yml понимает, что
# синхронизация кода завершена. Лежит в /tmp контейнера, а не в томе, поэтому
# при каждом старте гарантированно отсутствует и не «залипает» с прошлого раза.
#
# Без него `docker compose up --wait` объявляет контейнер готовым сразу после
# запуска процесса, деплой идёт делать `exec artisan migrate`, а rsync ниже в
# этот момент ещё копирует вендор — artisan падает на require autoload.php
# или vendor/composer/autoload_real.php, в зависимости от того, куда успел
# дойти обход каталогов.
READY_MARKER=/tmp/app-ready
rm -f "$READY_MARKER"

# Код «запечён» в образ по пути /app. При старте копируем его в общий
# volume /var/www (его же монтирует nginx), сохраняя смонтированные с хоста
# storage и .env.
#
# Вендор сносим перед синхронизацией, чтобы в томе не оставалось файлов от
# прошлой сборки. Удаляем только если в образе есть чем заменить: иначе снос
# рабочего вендора оставит том пустым.
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
# посреди миграций в логе деплоя. Проверяем и autoload_real.php: именно он
# копируется последним и его отсутствие означает оборванную синхронизацию.
if [ ! -f /var/www/vendor/autoload.php ] || [ ! -f /var/www/vendor/composer/autoload_real.php ]; then
  echo "entrypoint: вендор в /var/www неполон после синхронизации" >&2
  exit 1
fi

# Всё скопировано — только теперь контейнер можно считать готовым.
touch "$READY_MARKER"

exec "$@"
