#!/usr/bin/env bash
# Rebuild the test database as an empty, schema-only copy of the dev database.
# Tests run inside transactions, so the schema is all they need.
#
#   database/sql/create_test_database.sh            (uses root/root, HealthRishlpi)
#   DB_USERNAME=u DB_PASSWORD=p SOURCE_DB=x TEST_DB=y database/sql/create_test_database.sh
set -euo pipefail

user="${DB_USERNAME:-root}"
pass="${DB_PASSWORD:-root}"
source_db="${SOURCE_DB:-HealthRishlpi}"
test_db="${TEST_DB:-HealthRishlpi_test}"

mysql -u"$user" -p"$pass" -e "DROP DATABASE IF EXISTS \`$test_db\`; CREATE DATABASE \`$test_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysqldump -u"$user" -p"$pass" --no-data --skip-comments "$source_db" | mysql -u"$user" -p"$pass" "$test_db"

echo "Created $test_db from the schema of $source_db"
