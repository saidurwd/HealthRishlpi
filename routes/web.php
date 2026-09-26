<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\InstructionController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\PatientCategoryController;
use App\Http\Controllers\PatientCategoryNewController;
use App\Http\Controllers\PatientGradeController;
use App\Http\Controllers\PatientTypeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ThanaController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\VendorController;
use App\Support\LegacyRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| URLs keep the Yii app's "/<controllerId>/<actionId>/<id>" shape, because
| `os_menu.url` stores them in that form. Route names are
| "<controllerId>.<actionId>" with the exact Yii ids; the `acl` middleware
| uses them to look up `os_acl`.
|
| Each controller only gets the actions its Yii accessRules() let logged-in
| users reach. Forms post back to the same URL, as they did in Yii.
|
*/

// "/" and the old "index.php?r=controller/action&id=.." links
Route::get('/', function (Request $request) {
    return redirect(LegacyRoute::fromYiiQuery($request) ?? '/dashboard/index');
});

Route::middleware('guest')->group(function () {
    Route::match(['get', 'post'], '/site/login', [SiteController::class, 'login'])->name('site.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/site/logout', [SiteController::class, 'logout'])->name('site.logout');
    Route::get('/site/noaccess', [SiteController::class, 'noaccess'])->name('site.noaccess');

    // DashboardController::beforeAction() skipped the ACL check
    Route::get('/dashboard/index', [DashboardController::class, 'index'])->name('dashboard.index');
});

Route::middleware(['auth', 'acl'])->group(function () {
    // Configuration
    Route::crud('country', CountryController::class);
    Route::crud('state', StateController::class);
    Route::crud('city', CityController::class);
    Route::crud('district', DistrictController::class);
    Route::crud('thana', ThanaController::class);
    Route::crud('disease', DiseaseController::class);
    Route::crud('instruction', InstructionController::class);
    Route::crud('patientCategory', PatientCategoryController::class);
    Route::crud('patientCategoryNew', PatientCategoryNewController::class);
    Route::crud('patientGrade', PatientGradeController::class);
    Route::crud('patientType', PatientTypeController::class);
    Route::crud('service', ServiceController::class);

    // Access control
    Route::crud('department', DepartmentController::class);

    // Catalog
    Route::crud('productCategory', ProductCategoryController::class);
    Route::crud('product', ProductController::class);
    Route::crud('store', StoreController::class);
    Route::crud('unit', UnitController::class);
    Route::crud('batch', BatchController::class);
    Route::crud('vendor', VendorController::class);
    Route::crud('manufacturer', ManufacturerController::class);
});
