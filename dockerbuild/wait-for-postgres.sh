#!/bin/bash
# wait-for-postgres.sh

set -e

POSTGRES_HOST=$1
POSTGRES_PORT=$2
POSTGRES_USER=$3
POSTGRES_DB=$4
POSTGRES_PASSWORD=$5

until PGPASSWORD=$POSTGRES_PASSWORD psql -h "$POSTGRES_HOST" -p "$POSTGRES_PORT" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c '\q'; do
  >&2 echo "Postgres is unavailable - sleeping"
  sleep 1
done

>&2 echo "Postgres is up - executing command"
exec "${@:6}"
