# HealthRishlpi

Laravel 13 port of the Yii 1.1 "Health Program Software" (`~/Code/health`).
It started as a like-for-like copy (same database, URLs, permissions and
behaviour, UI moved from SmartAdmin to AdminLTE 4 / Bootstrap 5, no jQuery)
and is now being moved onto the Laravel ecosystem step by step.

## Setup

```sh
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate   # then set DB_* in .env
```

- Database: a copy of the Yii database (tables prefixed `os_`), then
  `php artisan migrate` (see [Database changes](#database-changes)).
- Sessions and cache are stored in files (`SESSION_DRIVER=file`,
  `CACHE_STORE=file`), not in the database.
- Tests: `database/sql/create_test_database.sh` builds `HealthRishlpi_test`
  from `database/schema/legacy-schema.sql` (the live schema before any
  migration, no data) plus the migrations, then `php artisan test`. Tests
  run in rolled-back transactions.
- Deployment: `deploy/README.md` (GitHub Actions → Deploy, zero-downtime
  releases on the production server, Nightwatch for errors).
- Checks (also run by GitHub Actions, `.github/workflows/ci.yml`, on
  MariaDB 11.4 like live): `vendor/bin/pint --test`,
  `vendor/bin/phpstan analyse` (Larastan, level 5, no baseline) and the tests.

## Database changes

Every change to the database is a migration in `database/migrations`, so
live can be brought up to date with `php artisan migrate --force`.
`database/schema/legacy-schema.sql` is the schema live had before the first
migration; keep it unchanged. Data is converted before anything is dropped,
and drops go in their own migration.

Deploys (`deploy/README.md`) take a full database snapshot before running
pending migrations. Rehearse new migrations on a copy of live first.

| Migration | What it does | Yii app |
|---|---|---|
| `2026_09_27_000001` / `000002` | Adds the spatie/laravel-permission tables (`os_roles`, `os_permissions`, pivots) plus labels | Unaffected |
| `2026_09_27_000003` | Copies user groups to roles (same ids), protected routes to permissions and `os_acl` to grants, so everyone keeps exactly the access they had; gives every user the role of their group | Unaffected |
| `migrations-after-cutover/2026_09_27_000004` | Drops `os_menu`, `os_acl`, `os_acl_action`, `os_acl_controller` and `os_user_group`, after checking every group and user has their role and archiving the tables to `storage/app/migration-archive/*.sql` | **Breaks it**: kept outside `database/migrations`, so deploys never run it; run it by hand once Yii is retired (see `deploy/README.md`) |

The conversion was checked on a copy of the data: every user's access to
every protected page (8 users x 224 routes) was the same before and after.

## Access control and menu

- User groups are roles (spatie/laravel-permission). `os_user.group_id` is
  still the user's group on the user form; the matching role is assigned on
  save. Group 1 (Super Users) passes every check (`Gate::before`).
- Every route in the `route.permission` middleware group requires the
  permission named like the route (`patient.admin`); users without it go to
  the "no access" page. A new route there needs its permission added by a
  migration (a test fails otherwise). Grant it in the User Group access
  matrix, which lists every permission by section.
- The sidebar is `config/menu.php` (AdminLTE `menu` style: `text`, `route`,
  `icon`, `can`, `submenu`), built by `App\Support\Menu`: items the user may
  not open, and parents left empty, are hidden.
- As in Yii, actions nobody ever set up in `os_acl` were open to every
  group; the conversion kept them open, and they can now be switched off.

## Activity log

spatie/laravel-activitylog records every create, change and delete made
through a model (who, when, old and new values), password changes (without
the hash) and access matrix changes. The Activity Log page (Access Control
menu, permission `activityLog.admin`, Super Users only by default) lists and
filters them. Bulk query updates are not logged. For that reason the "delete"
actions (status 2) save through the model, and only derived values (header
totals, draft linking, stock summary) are written in bulk. Entries are kept
three years (`ACTIVITYLOG_CLEAN_AFTER_DAYS`); `activitylog:clean` runs daily.
Sign-ins stay in the Audit Trail and page views in Visitor Statistics.

## Document numbers and stock

- Document numbers (INV#, PRE#, PAT#, SR#, SI#, PO/, MRR/, ST/) keep the Yii
  formats, but are chosen and saved under a database named lock per sequence
  (`App\Support\DocumentNumber`), so saves at the same moment no longer get
  the same number. The Yii app does not take the lock, so while both apps run
  a clash between them is still possible (`health:reconcile` reports it).
- Stock summary changes are one atomic `INSERT ... ON DUPLICATE KEY UPDATE
  quantity = quantity + ?`, so simultaneous approvals add up.

## Invoice screen

Create, edit, restore and special edit share one screen
(`invoice/workspace.blade.php`, `resources/js/invoice.js`):

- Patients (name, PAT# or mobile), products and prescriptions are searched on
  demand instead of shipping every patient, prescription and stock row in the
  page. The page went from 4.3 MB / 1 s to about 55 KB / 0.1 s.
- Choosing a product lists its stores and batches with expiry, free quantity
  and rate, suggesting the in-date batch that expires first. Expired batches
  are marked but can still be chosen, as before.
- Lines are added, changed and removed without reloading the page: only the
  lines are fetched again (under 1 KB instead of the whole page).
- Saving still posts the form to the same actions, so numbering, totals,
  approval and stock issue work as before (the invoice tests are unchanged).
  The JSON endpoints (`invoice.lines/patients/prescriptions/items/stock`) came
  with migration 000008, granted to every group that can create or edit
  invoices.
- The invoice list filters patients by name or PAT# (text box) instead of a
  dropdown of every patient, and status badges no longer query per row.

## Patient screens

- The list shows one compact row per patient (name, PAT#, mobile, sex,
  short age, category, registration date) with quick actions: open, new
  prescription, new invoice, edit. The Patient box searches name, PAT#,
  mobile and national ID.
- The patient page has a profile header, visit totals (prescriptions,
  invoices, approved billing, last visit), actions (new invoice with the
  patient chosen, new prescription, edit, printouts) and tabs for
  prescriptions, invoices and grouped details.
- The registration form shows every section at once with the key fields
  first; saving opens the patient's page. The age unit (Years/Months)
  selector had never rendered in this port; it does now.
- The prescription screen works like the invoice screen: medicines are
  searched on demand (with stock shown) and added or removed without
  reloading (`patient.medicines`/`patient.products`, migration 000009);
  the clinical notes sit beside them. Saving and numbering are unchanged.

## Monitoring

Under MONITORING in the menu (administrators; migration 000010):

- **System Health**: application, database (pending migrations), storage
  and disk space, scheduler heartbeat (every minute), daily reconciliation,
  backups per disk, off-site copy and encryption, Nightwatch, sign-in
  failures and today's logged errors.
- **Audit Log**: record changes with old and new values; each entry opens the
  record's full history.
- **Activity Log**: pages opened and actions taken by signed-in users, with
  time taken, IP and browser (`RecordActivity`, written after the response;
  kept 180 days). Background requests are left out.
- **Security Events** (`App\Support\SecurityLog`): sign-ins, failures (with
  the reason, which users never see), lockouts, sign-outs, refused access,
  password, account and permission changes, database exports, downloads and
  deletions, with IP and browser.
- **Login History**: the sign-in sessions both apps write (`os_audit_trail`)
  and failed attempts.
- Sign-in is rate limited: 5 failures a minute per username and IP.
- **About** (everyone): version (`config/app.php`), build (`release.json`
  from the deploy) and `CHANGELOG.md`.

## Porting conventions

A Gii-style admin/create/update/delete module is a `CrudController`
subclass (see `CityController`) plus `resources/views/<kebab id>/admin.blade.php`
(grid columns) and `_form.blade.php` (fields), registered with
`Route::crud('city', CityController::class)`.

| Yii | Here |
|---|---|
| `index.php?r=unit/update&id=5` | `/unit/update/5` (old links redirect from `/`) |
| Controller id / action id | Route name `unit.update` — exact Yii ids, also the permission name |
| `accessRules()` | Register only the actions logged-in users could reach |
| `beforeAction()` + `checkAccess()` + `os_acl` | `route.permission` middleware (`app/Http/Middleware/AuthorizeRoute.php`) + spatie permissions |
| `CActiveRecord` | `LegacyModel` subclass, `#[Table('unit', timestamps: false)]`, `#[Fillable]` = Yii "safe" attributes, `$attributes` = column defaults |
| Yii typecasting on save | `TypecastsLikeYii` (in `LegacyModel`): `''` becomes NULL in nullable numeric columns, 0 in NOT NULL ones; on insert, NULLs of NOT NULL columns are left out so MySQL fills the default (service invoice lines store `item` 0) |
| Relations (`country0`) | Same names — the plain names are the foreign key columns |
| `update_path()` / `update_alias()` / `get_full_path()` | `HasTreePath` trait |
| `attributeLabels()` / `rules()` | `HasAttributeLabels` trait, static `rules()`; messages use Yii wording (`lang/en/validation.php`) |
| select2 | `searchable` on `<x-form.select>`; grid dropdown filters with more than 10 options get it automatically |
| `CGridView` + `search()` | `App\Support\Grid` + `<x-grid>`; same query string (`Unit[attr]`, `Unit_sort`, `Unit_page`) and `compare()` semantics |
| `$('#x-grid').yiiGridView('update')` | `Grid.update('x-grid')` |
| `Yii::app()->params['x']` | `config('legacy.x')` |
| `Yii::app()->user->setFlash()` | `->with('success' / 'error', ...)` |
| jarviswidget | `<x-card>` |
| jquery.chained (`$('#batch').chained('#item, #store')`) | `data-chained="#item, #store"` on the select; options carry `data-chain="item\store"` (`resources/js/chained.js`) |
| "Add" line forms posting with `$.ajax` | `<form data-line-form="grid-id">` and `data-adjust-url` inputs (`resources/js/line-form.js`) |
| `layouts/report` printouts | `@extends('layouts.report')` + `<x-report-header>`; `@section('print-delay', 5000)` where Yii waited |
| `Yii::app()->user->name` (in SR#/SI#/PRE#/INV# numbers) | `User::loginName()`: what was typed at sign-in, kept in the session |
| `StockRequisition::genarateItemRate()`, pending quantities, item/store/batch lists | `App\Support\Stock` |
| `StockSummary::receiveStockSummary()` / `issueStockSummary()` | `StockSummary::receive()` / `issue()` |
| `Report` model SQL | `App\Support\Reports` (same SQL; request values bound or cast) |
| `User::get_date_time()`, `Product::number_format_currency()`, ... | `App\Support\YiiFormat` |

Behaviour kept on purpose:

- Passwords stay unsalted SHA1 so the Yii app can keep logging in against
  the same `os_user` rows until cutover.
- "Remember me" keeps users signed in for 30 days, like Yii's
  `allowAutoLogin`, without a `remember_token` column: the token is an HMAC of
  the user id and password hash (`User::getRememberToken()`). Changing the
  password or `APP_KEY` signs out remembered browsers.
- Request input is not trimmed or converted to null (middleware removed in
  `bootstrap/app.php`); models then apply Yii's typecast on save, and MySQL
  strict mode is off (`DB_STRICT=false`), so saved values match what Yii saved.

## Deliberate differences from the Yii app

Where the Yii app was visibly broken, the port does what the code intended:

- Store create/update crashed (the form called a missing `Store::getStores()`);
  it now uses the tree dropdown ProductCategory uses.
- Patient (Sub) Category grids printed the full path HTML-escaped; it is rendered.
- A delete blocked by a foreign key shows "This record cannot be deleted
  because other records refer to it." instead of the raw SQL error.
- Logout is a POST (CSRF-protected) instead of a GET link. Likewise the
  actions that changed data from GET links or GET AJAX calls are POSTs:
  the access matrix switches, quantity/store/rate adjustments, "add from
  PO/SR", "Make me issue", visitor truncate and the backup export, restore
  and cleanup.
- The menu is no longer edited on screen (`os_menu`, the Menus page and the
  ACL Controller / Action pages are gone); it lives in `config/menu.php`
  and only shows what the user may open (Yii showed every item).
- Audit trail durations of a single unit read " and minutes" in Yii
  (`returnInterval()` replaced the first two characters when there was no
  comma); they read "5 minutes".
- A user's replaced photo never deletes the shared default avatar
  (`male.png`). User forms only accept image files, and the change-password
  form no longer saves the SHA1 of an empty password.
- New patients: an age typed without a birth date now gives the birth date
  (Yii turned the age into 0 and left the date empty); a birth date still
  gives the age. Negative blood groups are stored (the form posted "O-" while
  the enum holds "O−" with U+2212, so Yii stored '').
- Invoices: the rate and note fields show only for manually priced services
  (Yii's check was a quoted, always-true string); a line that fails the
  stock check or misses a field reports why instead of silently not being
  added; changing a service line's quantity no longer fails (Yii referenced
  an undeclared `discountstatus`).
- Purchase receive: changing a line's quantity also updates its buy amount
  (Yii left it at the old quantity); "download all" with no files goes back
  to the receive, not to a page for the line's id. "Add selected" no longer
  skips purchase order line 1 (a workaround for the select-all checkbox).
- Stock issue: deleting a line loaded from a requisition works on PHP 8 (Yii
  called `count()` on a model); the issue total is recomputed when saved.
  Printouts of requisitions and issues list every line, not the first page.
- Store transfer: adding a line works (Yii called the buy-rate helper with
  too few arguments, a fatal error on PHP 8, which is why no transfers exist).
- Patient Category report: male and female counts are in their own columns
  (Yii took the first row as male, but the enum sorts Female first).
- Reports draw their pie charts as SVG (Highcharts is not bundled); the
  dashboard loads Chart.js from the same CDN as the Yii page, and its
  heatmap tooltips show the invoice count instead of a random number.
- Database backups are written to `storage/app/backups` (not the public
  uploads folder), streamed instead of built in memory. The one-click
  Restore was removed (it overwrote every table, the Yii app's too, from a
  browser click); restoring is a checked, manual step (`deploy/README.md`,
  "Backups"). Nightly encrypted full backups, off site too, come from
  spatie/laravel-backup.

## Port status

| Area | Status |
|---|---|
| Login, logout, layout | Done |
| Access control on spatie/laravel-permission, config menu | Done (drop migration waits for Yii's retirement) |
| Master data: Country, State, City, District, Thana, Disease, Instruction, Patient Category / Sub Category / Grade / Type, Service, Department, Product Category, Product, Store, Unit, Batch, Vendor, Manufacturer | Done |
| Access control: User, User Group (access matrix), User Status, Audit Trail, Visitor | Done |
| Patient, prescriptions, patient printouts | Done |
| Invoice | Done |
| Purchase Order / Receive (with documents, price comparison), Stock Requisition / Issue / Transfer | Done |
| Reports (15, with printouts), Dashboard, Database Backup | Done |

Every menu item opens a page of this app. Not ported: pages no menu item or
screen linked to.
