@extends('layouts.app')

@php $editing = $parent->exists; @endphp

@section('title', $editing ? 'Edit Purchase Receive' : 'New Purchase Receive')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase" :subtitle="$editing ? 'Edit Receive' : 'New Receive'" :breadcrumbs="$editing
        ? ['Purchase Receives' => route('purchaseReceive.admin'), $parent->receive_number => route('purchaseReceive.view', $parent->id), 'Update']
        : ['Purchase Receives' => route('purchaseReceive.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('purchaseReceive.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        @if ($editing)
            <a href="{{ route('purchaseReceive.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
            <a href="{{ route('purchaseReceive.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
        @endif
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loadpo-modal"><i class="fa fa-download"></i> LOAD FROM PO</button>
    </div>
    <x-card icon="fa fa-plus">
        <x-slot:title>@if ($editing) @include('purchase-receive._heading') @else New Receive @endif</x-slot:title>

        @include('purchase-receive._lines', ['mode' => 'draft'])

        <form method="post" action="{{ route('purchaseReceive.add') }}" id="purchase-receive-form" data-line-form="purchase-receive-grid" class="row g-2 align-items-start mt-3">
            @csrf
            <input type="hidden" name="parent" value="{{ $editing ? $parent->id : 0 }}">
            <div class="col-md-3"><x-form.select name="item" :label="''" :options="$items" empty="Select a Product" searchable /></div>
            <div class="col-md-2"><x-form.select name="store" :label="''" :options="$stores" empty="Select a Store" searchable /></div>
            <div class="col-md-1"><x-form.input name="quantity" :label="''" maxlength="20" placeholder="Quantity" /></div>
            <div class="col-md-1"><x-form.input name="buy_rate" :label="''" maxlength="18" placeholder="Buy Rate" /></div>
            <div class="col-md-1"><x-form.input name="rate" :label="''" maxlength="18" placeholder="Sale Rate" /></div>
            <div class="col-md-2"><x-form.input name="expiry" type="date" :label="''" placeholder="Expiry" /></div>
            <div class="col-md-1"><button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add</button></div>
        </form>

        <form method="post" id="purchase-receive-parent-form" enctype="multipart/form-data" class="mt-3 border-top pt-3">
            @csrf
            <x-form.errors />
            <div class="row g-2">
                @isset($statuses)
                    <div class="col-md-2"><x-form.select name="status" :label="$parent::label('status')" :options="$statuses" :value="$parent->status" /></div>
                @endisset
                <div class="col-md-3"><x-form.select name="supplier" :label="$parent::label('supplier')" :options="$vendors" :value="$parent->supplier" empty="Select a Supplier" required searchable /></div>
                <div class="col-md-5"><x-form.input name="comments" :label="$parent::label('comments')" :value="$parent->comments" maxlength="1000" placeholder="Comments" /></div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="doc_file">Documents</label>
                <input type="file" id="doc_file" name="doc_file[]" multiple class="form-control" accept=".jpg,.gif,.png,.doc,.docx,.pdf,.xl,.xls,.csv,.rtf,.odt,.tex,.txt,.ppt,.pptx,.zip,.7z,.rar,.bzip2,.gzip,.tar">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <button type="button" class="btn btn-default" onclick="window.history.back();">Back</button>
        </form>
    </x-card>

    {{-- LOAD ITEM FROM PURCHASE ORDER --}}
    <div class="modal fade" id="loadpo-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> LOAD ITEM FROM PURCHASE ORDER</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <x-grid id="loadpo-grid" :grid="$orders" :columns="[
                        ['header' => '', 'value' => fn ($row) => '<input type=\'checkbox\' class=\'form-check-input\' name=\'selectedPOIds[]\' value=\''.$row->id.'\'>', 'raw' => true, 'class' => 'text-center'],
                        ['header' => 'Order #', 'value' => fn ($row) => $row->parent0?->order_number, 'filter' => new \Illuminate\Support\HtmlString('<input type=\'text\' class=\'form-control form-control-sm\' name=\'PurchaseOrder[parentOrderNumber]\' value=\''.e(request('PurchaseOrder.parentOrderNumber')).'\'>'), 'name' => 'parentOrderNumber', 'sortable' => false],
                        ['name' => 'item', 'value' => fn ($row) => $row->item0?->title, 'filter' => $products, 'sortable' => false],
                        ['name' => 'quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->availableQuantity()), 'class' => 'text-end', 'sortable' => false],
                        ['header' => 'Supplier', 'value' => fn ($row) => $row->parent0?->supplier0?->title, 'name' => 'parentSupplier', 'sortable' => false, 'filter' => $vendors],
                        ['name' => 'created_on', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on), 'sortable' => false],
                        ['header' => '', 'value' => fn ($row) => '<button type=\'button\' class=\'btn btn-primary btn-sm\' data-add-po=\''.$row->id.'\'><i class=\'fa fa-plus\'></i></button>', 'raw' => true, 'class' => 'text-center'],
                    ]" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm me-auto" data-add-selected-po><i class="fa fa-plus"></i> ADD SELECTED</button>
                    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const addPo = async (ids) => {
                const response = await fetch(@js(route('purchaseReceive.addpo')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({ ids: ids.join(','), prp: @js($editing ? $parent->id : 0) }),
                });
                if (!response.ok) {
                    alert('Error occured. Please try again');
                }
                window.Grid.update('loadpo-grid');
                window.Grid.update('purchase-receive-grid');
            };
            document.addEventListener('click', (event) => {
                const single = event.target.closest('[data-add-po]');
                if (single) {
                    addPo([single.dataset.addPo]);
                }
                if (event.target.closest('[data-add-selected-po]')) {
                    const ids = [...document.querySelectorAll('#loadpo-grid input[name="selectedPOIds[]"]:checked')].map((box) => box.value);
                    if (ids.length) {
                        addPo(ids);
                    }
                }
            });
        })();
    </script>
@endpush
