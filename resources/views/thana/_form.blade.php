<div class="row">
    <div class="col-md-6">
        <x-form.select name="country" :label="$record::label('country')" :options="$countries" :value="$record->country" empty="Select a Country" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="state" :label="$record::label('state')" :options="$states" :value="$record->state" empty="Select a State" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="city" :label="$record::label('city')" :options="$cities" :value="$record->city" empty="Select a City" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="district" :label="$record::label('district')" :options="$districts" :value="$record->district" empty="Select a District" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Thana" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
