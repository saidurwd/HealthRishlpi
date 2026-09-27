<?php

/*
|--------------------------------------------------------------------------
| Sidebar menu
|--------------------------------------------------------------------------
|
| Laid out like the AdminLTE (jeroennoten/laravel-adminlte) `menu` option,
| converted from the Yii app's `os_menu` table. Each item has:
|
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
    ['text' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'fa fa-fw fa-home'],
    [
        'text' => 'Access Control',
        'icon' => 'fa fa-fw fa-lock',
        'submenu' => [
            ['text' => 'User Group', 'route' => 'userGroup.admin', 'can' => 'userGroup.admin'],
            ['text' => 'Department', 'route' => 'department.admin', 'can' => 'department.admin'],
            ['text' => 'User', 'route' => 'user.admin', 'can' => 'user.admin'],
            ['text' => 'Audit Trail', 'route' => 'auditTrail.admin', 'can' => 'auditTrail.admin'],
            ['text' => 'Visitor Statistics', 'route' => 'visitor.admin', 'can' => 'visitor.admin'],
        ],
    ],
    ['text' => 'Patient Directory', 'route' => 'patient.admin', 'icon' => 'fa fa-fw fa-database', 'can' => 'patient.admin'],
    ['text' => 'Invoice', 'route' => 'invoice.admin', 'icon' => 'fa fa-fw fa-money', 'can' => 'invoice.admin'],
    [
        'text' => 'Catalog',
        'icon' => 'fa fa-fw fa-anchor',
        'submenu' => [
            ['text' => 'Categories', 'route' => 'productCategory.admin', 'can' => 'productCategory.admin'],
            ['text' => 'Products', 'route' => 'product.admin', 'can' => 'product.admin'],
            ['text' => 'Store', 'route' => 'store.admin', 'can' => 'store.admin'],
            ['text' => 'Unit', 'route' => 'unit.admin', 'can' => 'unit.admin'],
            ['text' => 'Batch', 'route' => 'batch.admin', 'can' => 'batch.admin'],
            ['text' => 'Vendor', 'route' => 'vendor.admin', 'can' => 'vendor.admin'],
            ['text' => 'Manufacturer', 'route' => 'manufacturer.admin', 'can' => 'manufacturer.admin'],
        ],
    ],
    [
        'text' => 'Purchase',
        'icon' => 'fa fa-fw fa-briefcase',
        'submenu' => [
            ['text' => 'Order', 'route' => 'purchaseOrder.admin', 'can' => 'purchaseOrder.admin'],
            ['text' => 'Receive', 'route' => 'purchaseReceive.admin', 'can' => 'purchaseReceive.admin'],
            ['text' => 'Price Comparison', 'route' => 'purchaseReceive.price', 'can' => 'purchaseReceive.price'],
        ],
    ],
    [
        'text' => 'Issue',
        'icon' => 'fa fa-fw fa-delicious',
        'submenu' => [
            ['text' => 'Requisition', 'route' => 'stockRequisition.admin', 'can' => 'stockRequisition.admin'],
            ['text' => 'Issue', 'route' => 'stockIssue.admin', 'can' => 'stockIssue.admin'],
        ],
    ],
    ['text' => 'Store Transfer', 'route' => 'stockTransfer.admin', 'icon' => 'fa fa-fw fa-exchange', 'can' => 'stockTransfer.admin'],
    [
        'text' => 'Reports',
        'icon' => 'fa fa-fw fa-bar-chart-o',
        'submenu' => [
            ['text' => 'Stock Summary', 'route' => 'report.stocksummary', 'can' => 'report.stocksummary'],
            ['text' => 'Stock Receive', 'route' => 'report.stockreceive', 'can' => 'report.stockreceive'],
            ['text' => 'Sales Report', 'route' => 'report.sales', 'can' => 'report.sales'],
            ['text' => 'Expiration Report', 'route' => 'report.expiration', 'can' => 'report.expiration'],
            ['text' => 'Patient Register Medicine', 'route' => 'report.register', 'can' => 'report.register'],
            ['text' => 'Patient by Disease', 'route' => 'report.disease', 'can' => 'report.disease'],
            ['text' => 'Patient Category', 'route' => 'report.category', 'can' => 'report.category'],
            ['text' => 'Medicine Bill', 'route' => 'report.medicine', 'can' => 'report.medicine'],
            ['text' => 'Rehabilitation Service Bill', 'route' => 'report.service', 'can' => 'report.service'],
            ['text' => 'Income by Medicine', 'route' => 'report.mincome', 'can' => 'report.mincome'],
            ['text' => 'Period Wise Stock', 'route' => 'report.periodstock', 'can' => 'report.periodstock'],
            ['text' => 'Patient Invoices', 'route' => 'report.patinvoice', 'can' => 'report.patinvoice'],
            ['text' => 'Patient Register Physiotherapy', 'route' => 'report.registerphysio', 'can' => 'report.registerphysio'],
            ['text' => 'Invoice By Prescription', 'route' => 'report.prescription', 'can' => 'report.prescription'],
            ['text' => 'Physiotherapy Patient Contact Register', 'route' => 'report.contactregister', 'can' => 'report.contactregister'],
        ],
    ],
    [
        'text' => 'Configuration',
        'icon' => 'fa fa-fw fa-cogs',
        'submenu' => [
            ['text' => 'Patient Category', 'route' => 'patientCategoryNew.admin', 'can' => 'patientCategoryNew.admin'],
            ['text' => 'Patient Sub Category', 'route' => 'patientCategory.admin', 'can' => 'patientCategory.admin'],
            ['text' => 'Service', 'route' => 'service.admin', 'can' => 'service.admin'],
            ['text' => 'Disease', 'route' => 'disease.admin', 'can' => 'disease.admin'],
            ['text' => 'Instruction', 'route' => 'instruction.admin', 'can' => 'instruction.admin'],
            ['text' => 'Patient Type', 'route' => 'patientType.admin', 'can' => 'patientType.admin'],
            ['text' => 'Patient Grade', 'route' => 'patientGrade.admin', 'can' => 'patientGrade.admin'],
            ['text' => 'User Status', 'route' => 'userStatus.admin', 'can' => 'userStatus.admin'],
            ['text' => 'Country', 'route' => 'country.admin', 'can' => 'country.admin'],
            ['text' => 'State', 'route' => 'state.admin', 'can' => 'state.admin'],
            ['text' => 'City', 'route' => 'city.admin', 'can' => 'city.admin'],
            ['text' => 'District', 'route' => 'district.admin', 'can' => 'district.admin'],
            ['text' => 'Thana', 'route' => 'thana.admin', 'can' => 'thana.admin'],
            ['text' => 'Database Backup', 'route' => 'backup.admin', 'can' => 'backup.admin'],
        ],
    ],
];
