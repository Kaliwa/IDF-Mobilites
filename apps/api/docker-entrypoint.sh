#!/bin/sh
set -e

ensure_env_file() {
  if [ ! -f .env ]; then
    cp .env.example .env
  fi
}

normalize_database_url() {
  if [ -z "$DATABASE_URL" ]; then
    return
  fi

  case "$DATABASE_URL" in
    postgres://*)
      DATABASE_URL="$(printf '%s' "$DATABASE_URL" | sed 's|^postgres://|postgresql://|')"
      export DATABASE_URL
      ;;
  esac

  case "$DATABASE_URL" in
    *serverVersion=*)
      ;;
    *)
      case "$DATABASE_URL" in
        *\?*)
          DATABASE_URL="${DATABASE_URL}&serverVersion=16"
          ;;
        *)
          DATABASE_URL="${DATABASE_URL}?serverVersion=16"
          ;;
      esac
      export DATABASE_URL
      ;;
  esac
}

ensure_jwt_keys() {
  if [ -f config/jwt/private.pem ]; then
    return
  fi

  mkdir -p config/jwt
  openssl genpkey -out config/jwt/private.pem -algorithm RSA -pkeyopt rsa_keygen_bits:4096
  openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
}

configure_mercure_urls() {
  if [ -n "$MERCURE_HOSTPORT" ]; then
    export MERCURE_URL="http://${MERCURE_HOSTPORT}/.well-known/mercure"
  fi

  if [ -n "$MERCURE_PUBLIC_BASE_URL" ]; then
    export MERCURE_PUBLIC_URL="${MERCURE_PUBLIC_BASE_URL%/}/.well-known/mercure"
  fi
}

wait_for_database() {
  if [ -z "$DATABASE_URL" ]; then
    echo "DATABASE_URL is not set."
    return
  fi

  DB_HOST="$(printf '%s' "$DATABASE_URL" | sed -E 's|^[a-zA-Z0-9+.-]+://[^@]*@([^:/?]+).*|\1|')"
  DB_PORT="$(printf '%s' "$DATABASE_URL" | sed -nE 's|^[a-zA-Z0-9+.-]+://[^@]*@[^:/]+:([0-9]+).*|\1|p')"
  if [ -z "$DB_PORT" ]; then
    DB_PORT=5432
  fi

  echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
  for _ in $(seq 1 60); do
    if pg_isready -h "$DB_HOST" -p "$DB_PORT" >/dev/null 2>&1; then
      echo "Database is ready."
      return
    fi
    sleep 2
  done

  echo "Database is not ready after 120s, continuing anyway."
}

ensure_env_file
normalize_database_url
ensure_jwt_keys
configure_mercure_urls
wait_for_database

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:orientation:seed --no-interaction
php bin/console app:lines:sync --no-interaction || echo "Transit lines sync skipped (missing IDFM_API_KEY?)."

php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

exec "$@"
