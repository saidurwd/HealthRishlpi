#!/usr/bin/env bash
# Install a built release on the server and switch to it without downtime.
#
#   deploy/activate.sh <release.tar.gz>
#
# Layout under $DEPLOY_PATH (see deploy/README.md for the one-time setup):
#   releases/<timestamp>/   unpacked releases (the newest $KEEP_RELEASES kept)
#   shared/.env             production settings
#   shared/storage/         logs, sessions, cache, backups
#   backups/                database snapshots taken before migrations
#   current -> releases/…   what the web server serves (current/public)
#
# Settings come from $DEPLOY_PATH/deploy.env:
#   UPLOADS_PATH     the Yii app's uploads folder while both apps run, so
#                    photos and documents are shared (default shared/uploads)
#   HEALTH_URL       URL that must answer 200 after the switch (…/up)
#   RELOAD_COMMAND   e.g. "sudo systemctl reload php8.4-fpm"
#   RESTART_COMMAND  e.g. "sudo supervisorctl restart nightwatch-agent"
#   KEEP_RELEASES    default 5
set -euo pipefail

archive="$(realpath "$1")"
DEPLOY_PATH="${DEPLOY_PATH:?set DEPLOY_PATH to the deploy folder}"
[ -f "$DEPLOY_PATH/deploy.env" ] && source "$DEPLOY_PATH/deploy.env"
UPLOADS_PATH="${UPLOADS_PATH:-$DEPLOY_PATH/shared/uploads}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

release="$DEPLOY_PATH/releases/$(date +%Y%m%d%H%M%S)"
previous="$(readlink "$DEPLOY_PATH/current" 2>/dev/null || true)"

echo "Unpacking into $release"
mkdir -p "$release"
tar -xzf "$archive" -C "$release"

echo "Linking shared files"
ln -sfn "$DEPLOY_PATH/shared/.env" "$release/.env"
rm -rf "$release/storage" "$release/public/uploads"
ln -sfn "$DEPLOY_PATH/shared/storage" "$release/storage"
ln -sfn "$UPLOADS_PATH" "$release/public/uploads"

cd "$release"

# Migrations run while the previous release still serves: they only add
# (drops live in database/migrations-after-cutover and are never run here).
if ! php artisan migrate:status --pending --no-interaction | grep -q "No pending migrations"; then
    echo "Pending migrations: snapshot first"
    php artisan db:snapshot "$DEPLOY_PATH/backups" --no-interaction
    php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction
php artisan storage:link --no-interaction >/dev/null 2>&1 || true

echo "Switching current -> $release"
ln -sfn "$release" "$DEPLOY_PATH/current.new"
mv -Tf "$DEPLOY_PATH/current.new" "$DEPLOY_PATH/current"

[ -n "${RELOAD_COMMAND:-}" ] && eval "$RELOAD_COMMAND"
php artisan queue:restart --no-interaction >/dev/null
[ -n "${RESTART_COMMAND:-}" ] && eval "$RESTART_COMMAND"

if [ -n "${HEALTH_URL:-}" ]; then
    status="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$HEALTH_URL" || true)"
    if [ "$status" != "200" ]; then
        echo "Health check $HEALTH_URL answered $status" >&2
        if [ -n "$previous" ]; then
            echo "Rolling back to $previous" >&2
            ln -sfn "$previous" "$DEPLOY_PATH/current.new"
            mv -Tf "$DEPLOY_PATH/current.new" "$DEPLOY_PATH/current"
            [ -n "${RELOAD_COMMAND:-}" ] && eval "$RELOAD_COMMAND"
        fi
        rm -rf "$release"
        exit 1
    fi
fi

echo "Pruning old releases"
ls -1dt "$DEPLOY_PATH"/releases/*/ | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf

echo "Deployed $release"
