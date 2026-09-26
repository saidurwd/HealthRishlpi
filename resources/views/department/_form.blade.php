<div class="row">
    <div class="col-md-6">
        <x-form.select name="parent" :label="$record::label('parent')" :options="$parents" :value="$record->parent" empty="Select a Parent" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Department" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="code" :label="$record::label('code')" :value="$record->code" maxlength="4" placeholder="Code" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.textarea name="description" :label="$record::label('description')" :value="$record->description" />
    </div>
</div>
