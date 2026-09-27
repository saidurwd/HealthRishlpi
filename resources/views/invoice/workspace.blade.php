{{--
    The invoice screen (resources/js/invoice.js). $mode:
      create    new invoice; lines are the user's drafts until it is saved
      update    pending invoice: add/remove lines, approve
      rollback  deleted invoice: restore (super users)
      edit      special edit of an approved invoice: quantity changes move
                stock at once; no lines added or removed (super users)
    Patients, products and stock are searched on demand; lines are added and
    changed without reloading the page. Saving posts this page's form.
--}}
@php
    $titles = ['create' => 'New Invoice', 'update' => 'Edit Invoice', 'rollback' => 'Restore Invoice', 'edit' => 'Special Edit'];
    $submit = ['create' => 'Save Invoice', 'update' => 'Update Invoice', 'rollback' => 'Restore Invoice', 'edit' => 'Save Changes'][$mode];
    $canAddLines = $mode !== 'edit';
    $crumbs = ['Invoices' => route('invoice.admin')]
        + ($parent->exists ? [$parent->invoice_number => route('invoice.view', $parent->id)] : [])
        + [$titles[$mode]];
    $data = [
        'mode' => $mode,
        'parent' => $parent->exists ? $parent->id : 0,
        'currency' => (string) session('currency'),
        'medicineDiscount' => $medicineDiscount,
        'services' => $services,
        'patient' => $patient,
        'prescriptions' => $prescriptions,
        'prescription' => (string) old('prescription', $parent->prescription),
        'lines' => $initialLines,
        'requireCategory' => in_array($mode, ['update', 'rollback'], true),
        'urls' => [
            'lines' => route('invoice.lines'),
            'patients' => route('invoice.patients'),
            'prescriptions' => route('invoice.prescriptions'),
            'items' => route('invoice.items'),
            'stock' => route('invoice.stock'),
            'add' => route('invoice.add'),
            'adjust' => route($mode === 'edit' ? 'invoice.adjustmentEdit' : 'invoice.adjustment'),
            'delete' => route('invoice.delete', '__ID__'),
        ],
    ];
@endphp
@extends('layouts.app')

@section('title', $titles[$mode].' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" :subtitle="$titles[$mode]" :breadcrumbs="$crumbs" />
@endsection

@section('content')
    <div id="invoice-workspace" class="invoice-workspace">
        <div class="row g-3">
            <div class="col-xl-8">
                @if ($canAddLines)
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center gap-3">
                            <h3 class="card-title mb-0"><i class="fa fa-plus-circle text-primary"></i> Add item</h3>
                            <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Item type">
                                <input type="radio" class="btn-check" name="line-type" id="line-type-medicine" value="Medicine" checked>
                                <label class="btn btn-outline-primary" for="line-type-medicine"><i class="fa fa-medkit"></i> Medicine</label>
                                <input type="radio" class="btn-check" name="line-type" id="line-type-service" value="Service">
                                <label class="btn btn-outline-primary" for="line-type-service"><i class="fa fa-stethoscope"></i> Service</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="invoice-line-form" action="{{ route('invoice.add') }}" autocomplete="off" novalidate>
                                <input type="hidden" name="servicetype" value="Medicine">
                                <input type="hidden" name="parent" value="{{ $parent->exists ? $parent->id : '' }}">
                                <input type="hidden" name="store">
                                <input type="hidden" name="batch">

                                <div class="row g-2 align-items-end" data-for="Medicine">
                                    <div class="col-md-5">
                                        <label class="form-label small text-body-secondary" for="line-item">Product <kbd class="ms-1">/</kbd></label>
                                        <select id="line-item" name="item" placeholder="Search a product…"></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-body-secondary" for="line-stock">Store · Batch · Expiry</label>
                                        <select id="line-stock" class="form-select" disabled>
                                            <option value="">Pick a product first</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-body-secondary" for="line-quantity">Quantity</label>
                                        <input type="number" id="line-quantity" name="quantity" class="form-control" min="0" step="any" placeholder="Qty">
                                    </div>
                                    <div class="col-md-1 d-grid">
                                        <button type="submit" class="btn btn-primary" title="Add (Enter)"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>

                                <div class="row g-2 align-items-end" data-for="Service" hidden>
                                    <div class="col-md-4">
                                        <label class="form-label small text-body-secondary" for="line-service">Service</label>
                                        <select id="line-service" name="service" placeholder="Search a service…"></select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-body-secondary" for="line-discounttype">Discount</label>
                                        <select id="line-discounttype" name="discounttype" class="form-select">
                                            <option value="Percentage">Percent %</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-body-secondary" for="line-discountamount">Discount value</label>
                                        <input type="number" id="line-discountamount" name="discountamount" class="form-control" min="0" step="1" value="0">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-body-secondary" for="line-service-quantity">Qty</label>
                                        <input type="number" id="line-service-quantity" class="form-control" min="0" step="any" value="1">
                                    </div>
                                    <div class="col-md-2" data-for="Manual" hidden>
                                        <label class="form-label small text-body-secondary" for="line-rate">Rate</label>
                                        <input type="number" id="line-rate" name="rate" class="form-control" min="0" step="any" placeholder="Rate">
                                    </div>
                                    <div class="col-md-1 d-grid">
                                        <button type="submit" class="btn btn-primary" title="Add (Enter)"><i class="fa fa-plus"></i></button>
                                    </div>
                                    <div class="col-12" data-for="Manual" hidden>
                                        <input type="text" name="note" class="form-control form-control-sm" maxlength="400" placeholder="Note (optional)">
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-3 small text-body-secondary mt-2" id="line-preview" aria-live="polite"></div>
                                <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small" id="line-error" role="alert" hidden></div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="fa fa-list text-primary"></i> Items <span class="badge text-bg-secondary ms-1" data-total="count">0</span></h3>
                        @if ($mode === 'edit')
                            <span class="float-end small text-warning-emphasis"><i class="fa fa-warning"></i> Quantity changes move stock immediately</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 invoice-lines" id="invoice-lines">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:3rem">#</th>
                                    <th>Item / Service</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Amount</th>
                                    @if ($canAddLines)
                                        <th style="width:3rem"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="table-light fw-semibold">
                                <tr>
                                    <td colspan="4" class="text-end">Total</td>
                                    <td class="text-end" data-total="discount"></td>
                                    <td class="text-end" data-total="amount"></td>
                                    @if ($canAddLines)
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <form method="post" id="invoice-header" class="invoice-sidebar" autocomplete="off">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><i class="fa fa-file-text-o text-primary"></i> {{ $parent->invoice_number ?: 'Invoice' }}</h3>
                            @if ($parent->exists)
                                <span class="float-end">{!! \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::INVOICE) !!}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <x-form.errors />
                            @if ($parent->exists)
                                <p class="small text-body-secondary mb-3">
                                    {{ \App\Support\YiiFormat::dateTime($parent->invoice_date) }} · {{ $parent->invoiceBy?->full_name }}
                                </p>
                            @endif

                            <div class="mb-3">
                                <label class="form-label" for="invoice-patient">Patient</label>
                                <select id="invoice-patient" name="patient" placeholder="Search name, PAT# or mobile…" required></select>
                                <div class="form-text" id="invoice-patient-detail"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="invoice-prescription">Prescription</label>
                                <select id="invoice-prescription" name="prescription" class="form-select">
                                    <option value="">No prescription</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                @if (in_array($mode, ['update', 'rollback'], true))
                                    <div class="col-6">
                                        <label class="form-label" for="invoice-status">Status</label>
                                        <select id="invoice-status" name="status" class="form-select">
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected((string) old('status', $parent->status) === (string) $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-6">
                                    <span class="form-label d-block">Payment</span>
                                    <div class="btn-group w-100" role="group" aria-label="Payment status">
                                        @foreach (\App\Models\InvoiceParent::PAYMENT_STATUSES as $value => $label)
                                            <input type="radio" class="btn-check" name="payment_status" id="payment-{{ $value }}" value="{{ $value }}" @checked(old('payment_status', $parent->payment_status ?? 'Unpaid') === $value)>
                                            <label class="btn btn-outline-{{ $value === 'Paid' ? 'success' : 'secondary' }}" for="payment-{{ $value }}">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @if ($categories !== [])
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <x-form.select name="patient_category_new" :label="$parent::label('patient_category_new')" :options="$categoriesNew" :value="$parent->patient_category_new" empty="Category" searchable />
                                    </div>
                                    <div class="col-6">
                                        <x-form.select name="patient_category" :label="$parent::label('patient_category')" :options="$categories" :value="$parent->patient_category" empty="Sub category" searchable />
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label" for="invoice-comments">Comments</label>
                                <textarea id="invoice-comments" name="comments" class="form-control" rows="2" maxlength="1000">{{ old('comments', $parent->comments) }}</textarea>
                            </div>

                            <dl class="invoice-totals mb-0">
                                <div><dt>Items</dt><dd data-total="count">0</dd></div>
                                <div><dt>Gross</dt><dd data-total="gross"></dd></div>
                                <div><dt>Discount</dt><dd data-total="discount"></dd></div>
                                <div class="net"><dt>Net payable</dt><dd data-total="amount"></dd></div>
                            </dl>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1" id="invoice-save"><i class="fa fa-check"></i> {{ $submit }}</button>
                            <a href="{{ $parent->exists ? route('invoice.view', $parent->id) : route('invoice.admin') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="application/json" id="invoice-data">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
