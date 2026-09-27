#!/usr/bin/env bash
# Switch current back to the release before it. Code only: migrations are
# additive, so the older code keeps working on the newer schema. To undo
# data changes, restore the snapshot in backups/ taken before the migration.
#
#   deploy/rollback.sh
set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:?set DEPLOY_PATH to the deploy folder}"
[ -f "$DEPLOY_PATH/deploy.env" ] && source "$DEPLOY_PATH/deploy.env"

# Releases are named by timestamp: take the newest one older than current
current="$(basename "$(readlink -f "$DEPLOY_PATH/current")")"
previous="$(ls -1 "$DEPLOY_PATH/releases" | sort | awk -v current="$current" '$0 < current' | tail -n 1)"
[ -n "$previous" ] && previous="$DEPLOY_PATH/releases/$previous"

if [ -z "$previous" ]; then
    echo "No earlier release to roll back to" >&2
    exit 1
fi

ln -sfn "$previous" "$DEPLOY_PATH/current.new"
mv -Tf "$DEPLOY_PATH/current.new" "$DEPLOY_PATH/current"
[ -n "${RELOAD_COMMAND:-}" ] && eval "$RELOAD_COMMAND"
(cd "$DEPLOY_PATH/current" && php artisan queue:restart --no-interaction >/dev/null)
[ -n "${RESTART_COMMAND:-}" ] && eval "$RESTART_COMMAND"

echo "Rolled back to $previous"
