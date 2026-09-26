<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="250" placeholder="Patient Type" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="remarks" :label="$record::label('remarks')" :value="$record->remarks" maxlength="400" placeholder="Remarks" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
