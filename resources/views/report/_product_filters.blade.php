{{-- Category tree, products chained to it, store (stock reports) --}}
<div class="col-md-2"><x-form.select name="categoryid" :label="''" :options="$categoryOptions" :value="$f['cid']" empty="All Categories" class="form-select-sm" searchable /></div>
<div class="col-md-2"><x-form.select name="itemid" :label="''" :options="$itemOptions" :value="$f['itemid']" empty="All Products" class="form-select-sm" data-chained="#categoryid" /></div>
@if ($withStore ?? true)
    <div class="col-md-2"><x-form.select name="store" :label="''" :options="$storeOptions" :value="$f['store'] ?? ''" empty="All Stores" class="form-select-sm" searchable /></div>
@endif
