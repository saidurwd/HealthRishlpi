{{--
    Add-line form of the requisition / issue / transfer pages: product,
    store and batch chained like the invoice form. $storeField names the
    store field ('store' or 'store_from'); $storeTo adds the transfer's
    "to store" tree.
--}}
@php $storeField ??= 'store'; @endphp
<form method="post" action="{{ $action }}" id="{{ $formId }}" data-line-form="{{ $gridId }}" class="row g-2 align-items-start mt-3">
    @csrf
    <input type="hidden" name="parent" value="{{ $parent->exists ? $parent->id : 0 }}">
    <div class="col-md-3"><x-form.select name="item" :label="''" :options="$items" empty="Select a Product" searchable /></div>
    <div class="col-md-2"><x-form.select :name="$storeField" :label="''" :options="$stores" :empty="$storeField === 'store' ? 'Select a Store' : 'Select From Store'" data-chained="#item" /></div>
    <div class="col-md-3"><x-form.select name="batch" :label="''" :options="$batches" empty="Select a Batch" :data-chained="'#item, #'.$storeField" /></div>
    <div class="col-md-1"><x-form.input name="quantity" :label="''" maxlength="20" placeholder="Quantity" /></div>
    @isset($storeTo)
        <div class="col-md-2"><x-form.select name="store_to" :label="''" :options="$storeTo" empty="Select a Store" searchable /></div>
    @endisset
    <div class="col-md-1"><button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add</button></div>
</form>
