<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\InstructionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\PatientCategoryController;
use App\Http\Controllers\PatientCategoryNewController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientGradeController;
use App\Http\Controllers\PatientTypeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceiveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StockIssueController;
use App\Http\Controllers\StockRequisitionController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ThanaController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VisitorController;
use App\Support\LegacyRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| URLs keep the Yii app's "/<controllerId>/<actionId>/<id>" shape, so old
| bookmarks still work. Route names are "<controllerId>.<actionId>" with the
| exact Yii ids; the `route.permission` middleware requires the permission
| of the same name. A new route there needs its permission added by a
| migration (tests/Feature/AccessControlTest checks every route has one).
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
    Route::post('/dashboard/ajaxFilter', [DashboardController::class, 'ajaxFilter'])->name('dashboard.ajaxFilter');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
});

Route::middleware(['auth', 'route.permission'])->group(function () {
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

    Route::crud('userStatus', UserStatusController::class);

    // Access control
    Route::crud('department', DepartmentController::class);

    Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::match(['get', 'post'], 'edit/{id}', 'edit')->name('edit')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
    });

    // User groups are roles. The access matrix switches were GET links in Yii; they change data, so they are POSTs here
    Route::crud('userGroup', UserGroupController::class);
    Route::controller(UserGroupController::class)->prefix('userGroup')->name('userGroup.')->group(function () {
        Route::get('access/{id}', 'access')->name('access')->whereNumber('id');
        Route::post('turnon/{id}', 'turnon')->name('turnon')->whereNumber('id');
        Route::post('turnoff/{id}', 'turnoff')->name('turnoff')->whereNumber('id');
        Route::post('accessall', 'accessall')->name('accessall');
        Route::post('accessallc', 'accessallc')->name('accessallc');
    });

    // Patients and prescriptions
    Route::crud('patient', PatientController::class);
    Route::controller(PatientController::class)->prefix('patient')->name('patient.')->group(function () {
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('card/{id}', 'card')->name('card')->whereNumber('id');
        Route::get('rehabilitation/{id}', 'rehabilitation')->name('rehabilitation')->whereNumber('id');
        Route::get('registration/{id}', 'registration')->name('registration')->whereNumber('id');
        Route::get('prescription/{id}', 'prescription')->name('prescription')->whereNumber('id');
        Route::get('preblank/{id}', 'preblank')->name('preblank')->whereNumber('id');
        Route::match(['get', 'post'], 'newprescription/{id}', 'newprescription')->name('newprescription')->whereNumber('id');
        Route::match(['get', 'post'], 'editprescription/{id}', 'editprescription')->name('editprescription')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
        Route::post('addmedicine', 'addmedicine')->name('addmedicine');
        Route::post('removemedicine/{id}', 'removemedicine')->name('removemedicine')->whereNumber('id');
    });

    // Invoices. The quantity adjustments were GET requests in Yii; they change data, so they are POSTs here
    Route::controller(InvoiceController::class)->prefix('invoice')->name('invoice.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::match(['get', 'post'], 'rollback/{id}', 'rollback')->name('rollback')->whereNumber('id');
        Route::match(['get', 'post'], 'edit/{id}', 'edit')->name('edit')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('adjustment', 'adjustment')->name('adjustment');
        Route::post('adjustmentEdit', 'adjustmentEdit')->name('adjustmentEdit');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
    });

    // Purchase orders and receives. Adjustments and "add from PO" were GET requests in Yii; they are POSTs here
    Route::controller(PurchaseOrderController::class)->prefix('purchaseOrder')->name('purchaseOrder.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
    });
    Route::controller(PurchaseReceiveController::class)->prefix('purchaseReceive')->name('purchaseReceive.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('price', 'price')->name('price');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::get('edit/{id}', 'edit')->name('edit')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('addpo', 'addpo')->name('addpo');
        Route::post('adjustment', 'adjustment')->name('adjustment');
        Route::post('adjustmentEdit', 'adjustmentEdit')->name('adjustmentEdit');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
        Route::get('upload/{id}', 'upload')->name('upload')->whereNumber('id');
        Route::post('docupload', 'docupload')->name('docupload');
        Route::post('deletefile/{id}', 'deletefile')->name('deletefile')->whereNumber('id');
        Route::get('downloadfile/{id}', 'downloadfile')->name('downloadfile')->whereNumber('id');
        Route::get('download/{id}', 'download')->name('download')->whereNumber('id');
        Route::get('downloadall/{id}', 'downloadall')->name('downloadall')->whereNumber('id');
    });

    // Stock requisitions, issues and transfers ("make me issue", adjustments and "add from SR" were GET requests in Yii)
    Route::controller(StockRequisitionController::class)->prefix('stockRequisition')->name('stockRequisition.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('convertissue/{id}', 'convertissue')->name('convertissue')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
    });
    Route::controller(StockIssueController::class)->prefix('stockIssue')->name('stockIssue.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::get('edit/{id}', 'edit')->name('edit')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('addsr', 'addsr')->name('addsr');
        Route::post('adjustment', 'adjustment')->name('adjustment');
        Route::post('adjustmentEdit', 'adjustmentEdit')->name('adjustmentEdit');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
    });
    Route::controller(StockTransferController::class)->prefix('stockTransfer')->name('stockTransfer.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::get('view/{id}', 'view')->name('view')->whereNumber('id');
        Route::get('print/{id}', 'print')->name('print')->whereNumber('id');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
        Route::post('add', 'add')->name('add');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
        Route::post('remove/{id}', 'remove')->name('remove')->whereNumber('id');
    });

    // Reports: the screen posts its filters back to itself, "...print" takes them from the query string
    Route::controller(ReportController::class)->prefix('report')->name('report.')->group(function () {
        foreach (['stocksummary', 'stockreceive', 'sales', 'expiration', 'register', 'disease', 'category', 'medicine', 'service', 'mincome', 'periodstock', 'patinvoice', 'registerphysio', 'prescription', 'contactregister'] as $report) {
            Route::match(['get', 'post'], $report, $report)->name($report);
            Route::get($report.'print', $report.'print')->name($report.'print');
        }
        Route::get('allserviceprint', 'allserviceprint')->name('allserviceprint');
    });

    Route::controller(BackupController::class)->prefix('backup')->name('backup.')->group(function () {
        Route::get('admin', 'admin')->name('admin');
        Route::post('exportdatabase', 'exportdatabase')->name('exportdatabase');
        Route::post('restore/{id}', 'restore')->name('restore')->whereNumber('id');
        Route::post('cleanup', 'cleanup')->name('cleanup');
        Route::get('download/{id}', 'download')->name('download')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
    });

    Route::get('auditTrail/admin', [AuditTrailController::class, 'admin'])->name('auditTrail.admin');
    Route::post('auditTrail/delete/{id}', [AuditTrailController::class, 'delete'])->name('auditTrail.delete')->whereNumber('id');

    Route::get('visitor/admin', [VisitorController::class, 'admin'])->name('visitor.admin');
    Route::post('visitor/delete/{id}', [VisitorController::class, 'delete'])->name('visitor.delete')->whereNumber('id');
    Route::post('visitor/truncate', [VisitorController::class, 'truncate'])->name('visitor.truncate');

    // Catalog
    Route::crud('productCategory', ProductCategoryController::class);
    Route::crud('product', ProductController::class);
    Route::crud('store', StoreController::class);
    Route::crud('unit', UnitController::class);
    Route::crud('batch', BatchController::class);
    Route::crud('vendor', VendorController::class);
    Route::crud('manufacturer', ManufacturerController::class);
});
