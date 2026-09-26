<div class="row">
    <div class="col-md-6">
        <x-form.select name="parent" :label="$record::label('parent')" :options="$parents" :value="$record->parent" empty="Select a Parent" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Store" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="location" :label="$record::label('location')" :value="$record->location" maxlength="4" placeholder="Location" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="incharge" :label="$record::label('incharge')" :options="$users" :value="$record->incharge" empty="Select a User" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.textarea name="description" :label="$record::label('description')" :value="$record->description" />
    </div>
</div>
