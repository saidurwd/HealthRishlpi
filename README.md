# HealthRishlpi

Laravel 13 port of the Yii 1.1 "Health Program Software" (`~/Code/health`).
Phase 1 is a like-for-like copy: same database, same URLs, same permissions,
same behaviour, with the UI moved from SmartAdmin to AdminLTE 4 (Bootstrap 5,
no jQuery).

## Setup

```sh
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate   # then set DB_* in .env
```

- Database: an existing copy of the Yii database (tables prefixed `os_`),
  used as is — no schema changes are needed. There are no Laravel
  migrations; do not run `php artisan migrate`.
- Sessions and cache are stored in files (`SESSION_DRIVER=file`,
  `CACHE_STORE=file`), not in the database.
- Tests: `database/sql/create_test_database.sh` builds `HealthRishlpi_test`
  (schema only), then `php artisan test`. Tests run in rolled-back transactions.

## Database changes

The live database is not to be changed for now; the app must keep working
on the Yii schema. If a change is ever agreed, it goes into
`database/sql/live_changes.sql` as a new, numbered, re-runnable section, and
must stay compatible with the Yii app (add, never drop or rename). The same
file is applied to the live database before cutover.

## Porting conventions

A Gii-style admin/create/update/delete module is a `CrudController`
subclass (see `CityController`) plus `resources/views/<kebab id>/admin.blade.php`
(grid columns) and `_form.blade.php` (fields), registered with
`Route::crud('city', CityController::class)`.

| Yii | Here |
|---|---|
| `index.php?r=unit/update&id=5` | `/unit/update/5` (old links redirect from `/`) |
| Controller id / action id | Route name `unit.update` — exact Yii ids, used by the ACL check and the menu |
| `accessRules()` | Register only the actions logged-in users could reach |
| `beforeAction()` + `checkAccess()` | `acl` middleware (`app/Http/Middleware/CheckAcl.php`) |
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
- `os_acl` access defaults to allowed when no row exists; decisions are
  cached for an hour per user. Controller names match case-insensitively
  (`os_acl` stores `Unit`, the route id is `unit`).

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
- Menu "Groups" (a multi-select) could never be saved (Yii's length check
  rejected the array); it is stored comma-separated, as the rest of the Yii
  code reads it.
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
  uploads folder), streamed instead of built in memory.

## Port status

| Area | Status |
|---|---|
| Login, logout, ACL, menu, layout | Done |
| Master data: Country, State, City, District, Thana, Disease, Instruction, Patient Category / Sub Category / Grade / Type, Service, Department, Product Category, Product, Store, Unit, Batch, Vendor, Manufacturer | Done |
| Access control: User, User Group (access matrix), User Status, Menu, ACL Controller / Action, Audit Trail, Visitor | Done |
| Patient, prescriptions, patient printouts | Done |
| Invoice | Done |
| Purchase Order / Receive (with documents, price comparison), Stock Requisition / Issue / Transfer | Done |
| Reports (15, with printouts), Dashboard, Database Backup | Done |

Every active `os_menu` item opens a page of this app. Not ported: pages no
menu item or screen links to (the ACL Action "admin" list used a Bootstrap
widget that is not installed, so it failed in Yii too).
