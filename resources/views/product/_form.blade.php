<div class="row">
    <div class="col-md-6">
        <x-form.select name="category" :label="$record::label('category')" :options="$categories" :value="$record->category" empty="Select a Category" required searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Product" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="product_code" :label="$record::label('product_code')" :value="$record->product_code" maxlength="12" placeholder="Product Code" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="unit" :label="$record::label('unit')" :options="$units" :value="$record->unit" empty="Select a Unit" required searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="threshold_value" :label="$record::label('threshold_value')" :value="$record->threshold_value" maxlength="12" placeholder="Threshold Value" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="minimum_storage_limit" :label="$record::label('minimum_storage_limit')" :value="$record->minimum_storage_limit" maxlength="12" placeholder="Minimum Storage Limit" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.textarea name="description" :label="$record::label('description')" :value="$record->description" />
    </div>
</div>
