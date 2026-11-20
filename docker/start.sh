#!/bin/sh
set -eu
export DB_PASSWORD="$(cat /run/board/db_password)"
if [ -f state/app_key ]; then export APP_KEY="$(cat state/app_key)"; fi
if [ "$1" = init ]; then
  umask 077
  if [ ! -s state/app_key ]; then
    php -r 'echo "base64:".base64_encode(random_bytes(32));' > state/app_key.tmp
    mv state/app_key.tmp state/app_key
  fi
  export APP_KEY="$(cat state/app_key)"
  exec php artisan jobboard:install --no-interaction
fi
exec "$@"
