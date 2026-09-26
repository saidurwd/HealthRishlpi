<div class="row">
    <div class="col-md-6">
        <x-form.select name="country" :label="$record::label('country')" :options="$countries" :value="$record->country" empty="Select a Country" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="state" :label="$record::label('state')" :options="$states" :value="$record->state" empty="Select a State" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="City" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="city_2_code" :label="$record::label('city_2_code')" :value="$record->city_2_code" maxlength="2" placeholder="Code 2" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="city_3_code" :label="$record::label('city_3_code')" :value="$record->city_3_code" maxlength="3" placeholder="Code 3" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
