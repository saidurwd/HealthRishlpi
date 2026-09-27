<?php

/*
|--------------------------------------------------------------------------
| Sidebar menu
|--------------------------------------------------------------------------
|
| Laid out like the AdminLTE (jeroennoten/laravel-adminlte) `menu` option.
| Each item has:
|
|   header   a section title; hidden when nothing visible follows it
|   text     the label
|   route    route name of the page (or `url` for a plain link)
|   icon     Font Awesome classes (default: an empty circle)
|   can      permission needed to see the item; leave it out for pages
|            every signed-in user may open
|   submenu  child items; a parent without any visible child is hidden
|
| An item is highlighted when its route is the current route, and a parent
| is opened when one of its children is highlighted.
|
*/

return [
    ['text' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'fa fa-fw fa-tachometer'],

    ['header' => 'CLINIC'],
    ['text' => 'Patients', 'route' => 'patient.admin', 'icon' => 'fa fa-fw fa-user-injured', 'can' => 'patient.admin'],
    ['text' => 'New Patient', 'route' => 'patient.create', 'icon' => 'fa fa-fw fa-user-plus', 'can' => 'patient.create'],
    ['text' => 'Invoices', 'route' => 'invoice.admin', 'icon' => 'fa fa-fw fa-file-invoice-dollar', 'can' => 'invoice.admin'],
    ['text' => 'New Invoice', 'route' => 'invoice.create', 'icon' => 'fa fa-fw fa-cart-plus', 'can' => 'invoice.create'],

    ['header' => 'PHARMACY & STORE'],
    [
        'text' => 'Purchase',
        'icon' => 'fa fa-fw fa-truck',
        'submenu' => [
            ['text' => 'Purchase Orders', 'route' => 'purchaseOrder.admin', 'can' => 'purchaseOrder.admin'],
            ['text' => 'Goods Received', 'route' => 'purchaseReceive.admin', 'can' => 'purchaseReceive.admin'],
            ['text' => 'Price Comparison', 'route' => 'purchaseReceive.price', 'can' => 'purchaseReceive.price'],
        ],
    ],
    [
        'text' => 'Inventory',
        'icon' => 'fa fa-fw fa-boxes-stacked',
        'submenu' => [
            ['text' => 'Requisitions', 'route' => 'stockRequisition.admin', 'can' => 'stockRequisition.admin'],
            ['text' => 'Stock Issues', 'route' => 'stockIssue.admin', 'can' => 'stockIssue.admin'],
            ['text' => 'Store Transfers', 'route' => 'stockTransfer.admin', 'can' => 'stockTransfer.admin'],
        ],
    ],
    [
        'text' => 'Catalog',
        'icon' => 'fa fa-fw fa-pills',
        'submenu' => [
            ['text' => 'Products', 'route' => 'product.admin', 'can' => 'product.admin'],
            ['text' => 'Categories', 'route' => 'productCategory.admin', 'can' => 'productCategory.admin'],
            ['text' => 'Batches', 'route' => 'batch.admin', 'can' => 'batch.admin'],
            ['text' => 'Units', 'route' => 'unit.admin', 'can' => 'unit.admin'],
            ['text' => 'Stores', 'route' => 'store.admin', 'can' => 'store.admin'],
            ['text' => 'Vendors', 'route' => 'vendor.admin', 'can' => 'vendor.admin'],
            ['text' => 'Manufacturers', 'route' => 'manufacturer.admin', 'can' => 'manufacturer.admin'],
        ],
    ],

    ['header' => 'REPORTS'],
    [
        'text' => 'Patient Reports',
        'icon' => 'fa fa-fw fa-notes-medical',
        'submenu' => [
            ['text' => 'Register (Medicine)', 'route' => 'report.register', 'can' => 'report.register'],
            ['text' => 'Register (Physiotherapy)', 'route' => 'report.registerphysio', 'can' => 'report.registerphysio'],
            ['text' => 'Contact Register (Physiotherapy)', 'route' => 'report.contactregister', 'can' => 'report.contactregister'],
            ['text' => 'Patients by Disease', 'route' => 'report.disease', 'can' => 'report.disease'],
            ['text' => 'Patients by Category', 'route' => 'report.category', 'can' => 'report.category'],
        ],
    ],
    [
        'text' => 'Billing Reports',
        'icon' => 'fa fa-fw fa-chart-line',
        'submenu' => [
            ['text' => 'Sales', 'route' => 'report.sales', 'can' => 'report.sales'],
            ['text' => 'Patient Invoices', 'route' => 'report.patinvoice', 'can' => 'report.patinvoice'],
            ['text' => 'Invoices by Prescription', 'route' => 'report.prescription', 'can' => 'report.prescription'],
            ['text' => 'Medicine Bill', 'route' => 'report.medicine', 'can' => 'report.medicine'],
            ['text' => 'Rehabilitation Service Bill', 'route' => 'report.service', 'can' => 'report.service'],
            ['text' => 'Income by Medicine', 'route' => 'report.mincome', 'can' => 'report.mincome'],
        ],
    ],
    [
        'text' => 'Stock Reports',
        'icon' => 'fa fa-fw fa-warehouse',
        'submenu' => [
            ['text' => 'Stock Summary', 'route' => 'report.stocksummary', 'can' => 'report.stocksummary'],
            ['text' => 'Stock Received', 'route' => 'report.stockreceive', 'can' => 'report.stockreceive'],
            ['text' => 'Period-wise Stock', 'route' => 'report.periodstock', 'can' => 'report.periodstock'],
            ['text' => 'Expiring Stock', 'route' => 'report.expiration', 'can' => 'report.expiration'],
        ],
    ],

    ['header' => 'ADMINISTRATION'],
    [
        'text' => 'Users & Access',
        'icon' => 'fa fa-fw fa-users-gear',
        'submenu' => [
            ['text' => 'Users', 'route' => 'user.admin', 'can' => 'user.admin'],
            ['text' => 'User Groups & Permissions', 'route' => 'userGroup.admin', 'can' => 'userGroup.admin'],
            ['text' => 'Departments', 'route' => 'department.admin', 'can' => 'department.admin'],
            ['text' => 'User Statuses', 'route' => 'userStatus.admin', 'can' => 'userStatus.admin'],
        ],
    ],
    [
        'text' => 'Clinical Setup',
        'icon' => 'fa fa-fw fa-stethoscope',
        'submenu' => [
            ['text' => 'Services', 'route' => 'service.admin', 'can' => 'service.admin'],
            ['text' => 'Diseases', 'route' => 'disease.admin', 'can' => 'disease.admin'],
            ['text' => 'Instructions', 'route' => 'instruction.admin', 'can' => 'instruction.admin'],
            ['text' => 'Patient Categories', 'route' => 'patientCategoryNew.admin', 'can' => 'patientCategoryNew.admin'],
            ['text' => 'Patient Sub Categories', 'route' => 'patientCategory.admin', 'can' => 'patientCategory.admin'],
            ['text' => 'Patient Types', 'route' => 'patientType.admin', 'can' => 'patientType.admin'],
            ['text' => 'Patient Grades', 'route' => 'patientGrade.admin', 'can' => 'patientGrade.admin'],
        ],
    ],
    [
        'text' => 'Locations',
        'icon' => 'fa fa-fw fa-map-location-dot',
        'submenu' => [
            ['text' => 'Countries', 'route' => 'country.admin', 'can' => 'country.admin'],
            ['text' => 'States', 'route' => 'state.admin', 'can' => 'state.admin'],
            ['text' => 'Cities', 'route' => 'city.admin', 'can' => 'city.admin'],
            ['text' => 'Districts', 'route' => 'district.admin', 'can' => 'district.admin'],
            ['text' => 'Thanas', 'route' => 'thana.admin', 'can' => 'thana.admin'],
        ],
    ],
    ['text' => 'Database Backup', 'route' => 'backup.admin', 'icon' => 'fa fa-fw fa-database', 'can' => 'backup.admin'],

    ['header' => 'MONITORING'],
    ['text' => 'System Health', 'route' => 'systemHealth.admin', 'icon' => 'fa fa-fw fa-heart-pulse', 'can' => 'systemHealth.admin'],
    ['text' => 'Audit Log', 'route' => 'auditLog.admin', 'icon' => 'fa fa-fw fa-clock-rotate-left', 'can' => 'auditLog.admin'],
    ['text' => 'Activity Log', 'route' => 'activityLog.admin', 'icon' => 'fa fa-fw fa-list-check', 'can' => 'activityLog.admin'],
    ['text' => 'Security Events', 'route' => 'securityEvent.admin', 'icon' => 'fa fa-fw fa-shield-halved', 'can' => 'securityEvent.admin'],
    ['text' => 'Login History', 'route' => 'auditTrail.admin', 'icon' => 'fa fa-fw fa-right-to-bracket', 'can' => 'auditTrail.admin'],
    ['text' => 'Visitor Statistics (old app)', 'route' => 'visitor.admin', 'icon' => 'fa fa-fw fa-chart-bar', 'can' => 'visitor.admin'],

    ['header' => 'HELP'],
    ['text' => 'About', 'route' => 'site.about', 'icon' => 'fa fa-fw fa-circle-info'],
];
