@extends('layouts.app')

@php $editing = $parent->exists; @endphp

@section('title', $editing ? 'Edit Stock Issue' : 'New Stock Issue')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Issues" :subtitle="$editing ? 'Edit Issue' : 'New Issue'" :breadcrumbs="$editing
        ? ['Stock Issues' => route('stockIssue.admin'), $parent->issue_number => route('stockIssue.view', $parent->id), 'Update']
        : ['Stock Issues' => route('stockIssue.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockIssue.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        @if ($editing)
            <a href="{{ route('stockIssue.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
            <a href="{{ route('stockIssue.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
        @else
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loadsr-modal"><i class="fa fa-download"></i> LOAD FROM SR</button>
        @endif
    </div>
    <x-card icon="fa fa-plus">
        <x-slot:title>@if ($editing) @include('stock-issue._heading') @else New Issue @endif</x-slot:title>
        @include('stock-issue._lines', ['mode' => 'draft'])
        @include('stock._line_form', ['action' => route('stockIssue.add'), 'formId' => 'stock-issue-form', 'gridId' => 'stock-issue-grid'])
        @include('stock._parent_form', ['formId' => 'stock-issue-parent-form'])
    </x-card>

    @unless ($editing)
        {{-- LOAD ITEM FROM STOCK REQUISITION --}}
        <div class="modal fade" id="loadsr-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"><i class="fa fa-refresh"></i> LOAD ITEM FROM STOCK REQUISITION</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <x-grid id="loadsr-grid" :grid="$requisitions" :columns="[
                            ['header' => 'Req#', 'value' => fn ($row) => $row->parent0?->requisition_number, 'name' => 'parentRequisitionNumber', 'sortable' => false,
                                'filter' => new \Illuminate\Support\HtmlString('<input type=\'text\' class=\'form-control form-control-sm\' name=\'StockRequisition[parentRequisitionNumber]\' value=\''.e(request('StockRequisition.parentRequisitionNumber')).'\'>')],
                            ['name' => 'item', 'value' => fn ($row) => $row->item0?->title, 'filter' => $products, 'sortable' => false],
                            ['name' => 'store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A', 'filter' => $storeTree, 'sortable' => false],
                            ['name' => 'batch', 'header' => 'Expiry', 'value' => fn ($row) => $row->batch0?->title, 'sortable' => false],
                            ['name' => 'quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->availableQuantity()), 'class' => 'text-end', 'sortable' => false],
                            ['name' => 'rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end', 'sortable' => false],
                            ['name' => 'amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end', 'sortable' => false],
                            ['name' => 'created_on', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on), 'sortable' => false],
                            ['header' => '', 'value' => fn ($row) => '<button type=\'button\' class=\'btn btn-primary btn-sm\' data-add-sr=\''.$row->id.'\'><i class=\'fa fa-plus\'></i></button>', 'raw' => true, 'class' => 'text-center'],
                        ]" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-add-sr]');
            if (!button) {
                return;
            }
            const response = await fetch(@js(route('stockIssue.addsr')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: new URLSearchParams({ id: button.dataset.addSr }),
            });
            if (!response.ok) {
                const data = response.status === 422 ? await response.json() : null;
                alert(data ? Object.values(data.errors).flat().join('\n') : 'Error occured. Please try again');
            }
            window.Grid.update('loadsr-grid');
            window.Grid.update('stock-issue-grid');
        });
    </script>
@endpush
