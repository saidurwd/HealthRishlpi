@extends('layouts.app')

@section('title', $parent->invoice_number.' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" subtitle="Invoice Details" :breadcrumbs="['Invoices' => route('invoice.admin'), $parent->invoice_number]" />
@endsection

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        <a href="{{ route('invoice.admin') }}" class="btn btn-outline-secondary"><i class="fa fa-list"></i> All invoices</a>
        @can('invoice.create')
            <a href="{{ route('invoice.create') }}" class="btn btn-outline-primary"><i class="fa fa-plus"></i> New</a>
        @endcan
        @if ($parent->isEditable())
            @can('invoice.update')
                <a href="{{ route('invoice.update', $parent->id) }}" class="btn btn-primary"><i class="fa fa-pencil"></i> Edit / Approve</a>
            @endcan
        @endif
        @if ($parent->canSpecialEdit() && auth()->user()->isSuper())
            <a href="{{ route('invoice.edit', $parent->id) }}" class="btn btn-warning"><i class="fa fa-edit"></i> Special edit</a>
        @endif
        @if ($parent->canRollback() && auth()->user()->isSuper())
            <a href="{{ route('invoice.rollback', $parent->id) }}" class="btn btn-warning"><i class="fa fa-rotate-left"></i> Restore</a>
        @endif
        <a href="{{ route('invoice.print', $parent->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> Print</a>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h3 class="card-title mb-0">{{ $parent->invoice_number }}</h3>
                    {!! \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::INVOICE) !!}
                    <span @class(['badge', 'text-bg-success' => $parent->payment_status === 'Paid', 'text-bg-secondary' => $parent->payment_status !== 'Paid'])>{{ $parent->payment_status ?: 'Unpaid' }}</span>
                    <span class="ms-auto small text-body-secondary">{{ \App\Support\YiiFormat::dateTime($parent->invoice_date) }} · Order by {{ $parent->invoiceBy?->full_name }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:3rem">#</th>
                                <th>Item / Service</th>
                                <th>Store · Batch</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payload['lines'] as $line)
                                <tr>
                                    <td class="text-center text-body-secondary">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $line['title'] }}</div>
                                        <div class="small text-body-secondary">{{ $line['type'] }}@if ($line['note']) · {{ $line['note'] }}@endif</div>
                                    </td>
                                    <td class="small">
                                        @if ($line['type'] === 'Medicine')
                                            {{ $line['store'] ?? 'N/A' }} · {{ $line['batch'] ?? 'N/A' }}
                                            @if ($line['expiry'])<div class="text-body-secondary">exp {{ $line['expiry'] }}</div>@endif
                                        @else
                                            <span class="text-body-secondary">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $line['quantity'] }}@if ($line['type'] === 'Medicine') {{ $line['uom'] }}@endif</td>
                                    <td class="text-end">{{ $line['rate'] }}</td>
                                    <td class="text-end text-body-secondary">{{ $line['discount'] }}</td>
                                    <td class="text-end fw-semibold">{{ $line['amount'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-body-secondary py-4">No items</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="5" class="text-end">Total</td>
                                <td class="text-end">{{ $payload['totals']['discount'] }}</td>
                                <td class="text-end">{{ $payload['totals']['amount'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-user text-primary"></i> Patient</h3></div>
                <div class="card-body">
                    @if ($parent->patient0)
                        <a href="{{ route('patient.view', $parent->patient0->id) }}" class="fw-semibold">{{ $parent->patient0->name }}</a>
                        <div class="small text-body-secondary">
                            {{ $parent->patient0->pat_id }} · {{ $parent->patient0->sex }}, {{ trim($parent->patient0->ageText()) }}
                            @if ($parent->patient0->mobile) · {{ $parent->patient0->mobile }}@endif
                        </div>
                    @else
                        <span class="text-body-secondary">Unknown patient</span>
                    @endif
                    @if ($prescription)
                        <div class="mt-2 small">Prescription <strong>{{ $prescription->pre_number }}</strong></div>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <dl class="invoice-totals mb-0">
                        <div><dt>Items</dt><dd>{{ $payload['totals']['count'] }}</dd></div>
                        <div><dt>Gross</dt><dd>{{ $payload['totals']['gross'] }}</dd></div>
                        <div><dt>Discount</dt><dd>{{ $payload['totals']['discount'] }}</dd></div>
                        <div class="net"><dt>Net payable</dt><dd>{{ $payload['totals']['amount'] }}</dd></div>
                    </dl>
                    @if ($parent->comments)
                        <h4 class="h6 mt-3 mb-1">Comments</h4>
                        <p class="mb-0">{{ $parent->comments }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
