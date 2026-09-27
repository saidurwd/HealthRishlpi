# Deploying to the production server

Deploys run from GitHub: **Actions → Deploy → Run workflow**. The workflow runs
the full CI (Pint, Larastan, tests on MariaDB 11.4). It then builds the release
(Composer without dev packages, Vite assets), uploads it over SSH and runs
`deploy/activate.sh` on the server. That script:

1. unpacks the release into `releases/<timestamp>/` and links the shared `.env`,
   `storage/` and uploads folder;
2. if migrations are pending, takes a full database snapshot into `backups/`
   (`php artisan db:snapshot`; the deploy stops if it fails), then migrates;
3. caches config, routes, views and events;
4. switches `current` to the new release in one step and reloads PHP-FPM;
5. checks `HEALTH_URL`. If it does not answer 200, it switches back to the
   previous release, removes the failed one and fails the workflow;
6. keeps the newest `KEEP_RELEASES` releases.

Migrations in `database/migrations` only add to the database, so the Yii app
keeps working and older code keeps working on the newer schema. The drops in
`database/migrations-after-cutover` are never run by a deploy (see Cutover).

Rehearsed locally: a fresh deploy with migrations, a deploy with nothing
pending, a rollback, and a failed health check.

## One-time server setup

Requirements: Linux, PHP 8.3+ (8.4 recommended) with `pdo_mysql`, `gd`, `zip`,
`mbstring`, `xml`, `curl`, `intl`; the `mariadb-dump` (or `mysqldump`) client;
`curl`; `tar`. Node and Composer are not needed on the server.

```sh
# As the deploy user (who owns the files; the web server user must be able
# to write shared/storage and the uploads folder)
DEPLOY_PATH=/var/www/healthrishlpi
mkdir -p $DEPLOY_PATH/{releases,backups,incoming}
mkdir -p $DEPLOY_PATH/shared/storage/{app/public,framework/cache/data,framework/sessions,framework/views,logs}
cp .env.example $DEPLOY_PATH/shared/.env          # then edit, see below
chmod 600 $DEPLOY_PATH/shared/.env
```

`shared/.env` for production:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<laravel hostname>
APP_KEY=            # generate once: php artisan key:generate --show (keep it; changing it signs everyone out)
DB_HOST=...         # the live database, same as the Yii app
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
LOG_STACK=daily
NIGHTWATCH_ENABLED=true
NIGHTWATCH_TOKEN=   # from nightwatch.laravel.com, production environment

# Nightly encrypted backups of the database and uploads (see "Backups")
BACKUP_DISKS=backup_local,backup_offsite
BACKUP_ARCHIVE_PASSWORD=   # long random string; store it in your password manager too
BACKUP_S3_KEY=
BACKUP_S3_SECRET=
BACKUP_S3_BUCKET=
BACKUP_S3_REGION=
BACKUP_S3_ENDPOINT=        # e.g. https://s3.eu-central-003.backblazeb2.com (empty for AWS)
BACKUP_NOTIFY_MAIL=        # optional; needs MAIL_* set up
```

`$DEPLOY_PATH/deploy.env`:

```sh
# While Yii runs, share its uploads folder (user photos, store documents)
UPLOADS_PATH=/var/www/<yii app>/uploads
HEALTH_URL=https://<laravel hostname>/up
RELOAD_COMMAND="sudo systemctl reload php8.4-fpm"
RESTART_COMMAND="sudo supervisorctl restart healthrishlpi-nightwatch"
KEEP_RELEASES=5
```

Allow the deploy user to run exactly those two commands without a password
(`/etc/sudoers.d/healthrishlpi`):

```
deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm, /usr/bin/supervisorctl restart healthrishlpi-nightwatch
```

Web server: a separate hostname from the Yii app, document root
`$DEPLOY_PATH/current/public`, PHP-FPM, every request to `index.php`. Nginx:

```nginx
server {
    server_name <laravel hostname>;
    root /var/www/healthrishlpi/current/public;
    index index.php;
    client_max_body_size 20m;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}
```

(`$realpath_root` makes PHP-FPM pick up the new release after the switch.)

Nightwatch agent (supervisor, `/etc/supervisor/conf.d/healthrishlpi-nightwatch.conf`):

```ini
[program:healthrishlpi-nightwatch]
command=php /var/www/healthrishlpi/current/artisan nightwatch:agent
user=deploy
autostart=true
autorestart=true
stdout_logfile=/var/www/healthrishlpi/shared/storage/logs/nightwatch-agent.log
redirect_stderr=true
```

Nightwatch settings: request payloads are not captured
(`NIGHTWATCH_CAPTURE_REQUEST_PAYLOAD=false`, the default). Exception messages can
still contain values, such as a failed query's bindings. Check that sending
them to Nightwatch fits your rules for patient data.

Scheduler (crontab of the deploy user):

```
* * * * * cd /var/www/healthrishlpi/current && php artisan schedule:run >> /dev/null 2>&1
```

GitHub (Settings → Secrets and variables → Actions, and an environment named
`production`, optionally with required reviewers):

| Secret | Value |
|---|---|
| `DEPLOY_HOST` | server hostname or IP |
| `DEPLOY_PORT` | SSH port (optional, default 22) |
| `DEPLOY_USER` | the deploy user |
| `DEPLOY_PATH` | e.g. `/var/www/healthrishlpi` |
| `DEPLOY_SSH_KEY` | private key of a key pair made for deploys; its public key goes in the deploy user's `~/.ssh/authorized_keys` |
| `DEPLOY_KNOWN_HOSTS` | output of `ssh-keyscan -p <port> <host>` |

## Backups

Every night (scheduler, `routes/console.php`):

- 01:00 `backup:clean` keeps every backup for 7 days, then dailies for 16 days,
  weeklies for 8 weeks, monthlies for 4 months and yearlies for 2 years;
- 01:30 `health:backup` zips the whole database (every table, the Yii app's
  too) and the uploads folder, encrypts the zip with AES-256
  (`BACKUP_ARCHIVE_PASSWORD`), checks it, and copies it to every disk in
  `BACKUP_DISKS`: `storage/app/laravel-backups/` on the server and the
  S3-compatible bucket. It refuses to copy off site without a password.
- 07:30 `backup:monitor` fails if a disk has no backup from the last day.

Failures show up in Nightwatch as failed scheduled tasks, and by mail if
`BACKUP_NOTIFY_MAIL` is set. Run `php artisan health:backup` by hand once
after setting up, and check the bucket.

The one-click restore on the Database Backup page was removed: it overwrote
every table, the Yii app's included, from a browser click. To restore:

1. Download the zip (`backup:list` shows the disks) and extract it with a
   tool that supports AES zip encryption, e.g. `7z x <file>.zip` with the
   password.
2. Load `db-dumps/*.sql.gz` into a **new** database first and check it:
   `gunzip -c db-dumps/*.sql.gz | mariadb <new database>`.
3. When it is right, put the site in maintenance mode (`php artisan down`),
   take a snapshot of the current database (`php artisan db:snapshot …`),
   load the dump into the live database, and `php artisan up`.
4. Copy back any upload files needed from `uploads/`.

## Rolling back

```sh
DEPLOY_PATH=/var/www/healthrishlpi $DEPLOY_PATH/current/deploy/rollback.sh
```

This rolls back code only. To undo a migration's data changes, restore the
snapshot from `backups/` taken just before it (test the restore on a copy first).

## Parallel run (both apps on the live database)

1. Set up the server as above and deploy. The first deploy snapshots the
   database and runs the conversion migrations. Yii keeps working.
2. Straight away, before anyone uses the Laravel app, record the baseline:
   `php artisan health:reconcile --baseline` (in `current/`). It notes the
   inconsistencies the data already has, so later runs only report new ones.
   On a copy of today's data these were: 29 stock rows that differ from their
   movements, 40 invoice and 5 issue totals that differ from their lines,
   8 document numbers used twice, and 546 prescription medicines whose
   prescription was deleted.
3. Every morning the scheduler runs `health:reconcile`. A failed run
   (visible in Nightwatch, details in `storage/app/reconcile/latest.json`)
   means something new is inconsistent: look at the listed records, find
   which app wrote them (`created_by`, `created_on`), and fix the cause
   before going on.
4. Rules while both apps run:
   - Manage users, groups and permissions in the Laravel app. The Yii access
     manager (`os_acl`) no longer affects Laravel, and Laravel's permissions
     do not affect Yii. Users and group changes made in Yii are picked up at
     the user's next Laravel login.
   - Move staff over module by module (e.g. reports, then patients and
     prescriptions, then invoices, then purchasing and stock). Each module
     has one app at a time, and staff use Laravel for it only after they
     have checked it.
5. When every module runs on Laravel and the reports stay clean, plan the
   cutover.

## Cutover (once the Yii app is retired)

1. Take the Yii app offline (its site disabled), then run
   `php artisan health:reconcile` one last time and check it is clean.
2. Take a backup: `php artisan db:snapshot $DEPLOY_PATH/backups` in `current/`.
3. `php artisan migrate --force --path=database/migrations-after-cutover` drops
   the old menu/ACL tables, `os_visitor` (the old page-view statistics) and
   the tables no longer used (`os_cache`, `os_yiisession`, `os_sessions`).
   It archives them to `storage/app/migration-archive/` first and refuses if
   any group or user lacks their role.
4. Move the uploads folder from the Yii app to `shared/uploads`, remove
   `UPLOADS_PATH` from `deploy.env` and deploy once more.
5. Remove the `health:reconcile` schedule from `routes/console.php` (Phase 4
   replaces it with a stock integrity check) and archive the Yii code.
