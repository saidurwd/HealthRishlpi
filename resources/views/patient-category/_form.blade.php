<div class="row">
    <div class="col-md-6">
        <x-form.select name="parent" :label="$record::label('parent')" :options="$parents" :value="$record->parent" empty="Select a Parent" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Sub Category" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
