<div class="row">
    <div class="col-md-6">
        <x-form.select name="parent" :label="$record::label('parent')" :options="$parents" :value="$record->parent" empty="Select a Parent" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Service" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="rate" :label="$record::label('rate')" :value="$record->rate" maxlength="150" placeholder="Rate" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="rate_status" :label="$record::label('rate_status')" :options="['Auto' => 'Auto', 'Manual' => 'Manual']" :value="$record->rate_status" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="discount" :label="$record::label('discount')" :options="['No' => 'No', 'Yes' => 'Yes']" :value="$record->discount" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="service_type" :label="$record::label('service_type')" :options="['Consultation' => 'Consultation', 'Service' => 'Service']" :value="$record->service_type" empty="Select Service Type" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="service_grade" :label="$record::label('service_grade')" :options="$grades" :value="$record->service_grade" empty="Select a Grade" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="ordering" :label="$record::label('ordering')" :value="$record->ordering" maxlength="11" placeholder="Ordering" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::STATUSES" :value="$record->status" />
    </div>
</div>
