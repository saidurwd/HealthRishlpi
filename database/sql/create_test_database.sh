#!/usr/bin/env bash
# Rebuild the test database from database/schema/legacy-schema.sql (the
# schema live had before any Laravel migration: os_ tables, no data), then
# run database/migrations on top, exactly as they will run on live.
# Tests run inside transactions, so the schema is all they need.
#
#   database/sql/create_test_database.sh            (uses root/root, HealthRishlpi_test)
#   DB_HOST=h DB_USERNAME=u DB_PASSWORD=p TEST_DB=y database/sql/create_test_database.sh
#
# Refresh the snapshot from a database that matches live (schema only):
#   mariadb-dump --no-data --skip-comments --skip-add-drop-table --skip-dump-date <db> \
#     | sed -E 's/ AUTO_INCREMENT=[0-9]+//' > database/schema/legacy-schema.sql
set -euo pipefail

host="${DB_HOST:-127.0.0.1}"
user="${DB_USERNAME:-root}"
pass="${DB_PASSWORD:-root}"
test_db="${TEST_DB:-HealthRishlpi_test}"
schema="$(dirname "$0")/../schema/legacy-schema.sql"
client="$(command -v mariadb || command -v mysql)"

"$client" -h"$host" -u"$user" -p"$pass" -e "DROP DATABASE IF EXISTS \`$test_db\`; CREATE DATABASE \`$test_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"$client" -h"$host" -u"$user" -p"$pass" "$test_db" < "$schema"

cd "$(dirname "$0")/../.."
# The test database gets the after-cutover migrations too, so they stay tested
for path in database/migrations database/migrations-after-cutover; do
    DB_HOST="$host" DB_USERNAME="$user" DB_PASSWORD="$pass" DB_DATABASE="$test_db" php artisan migrate --force --no-interaction --path="$path"
done

echo "Created $test_db from $schema and the migrations"
