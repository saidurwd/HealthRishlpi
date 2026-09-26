<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Country" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="country_2_code" :label="$record::label('country_2_code')" :value="$record->country_2_code" maxlength="2" placeholder="Code 2" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="country_3_code" :label="$record::label('country_3_code')" :value="$record->country_3_code" maxlength="3" placeholder="Code 3" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
