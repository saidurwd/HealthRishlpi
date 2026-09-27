<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Group" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.textarea name="details" :label="$record::label('details')" :value="$record->details" rows="2" placeholder="Details" />
    </div>
</div>
