<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Vendor" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="email" :label="$record::label('email')" :value="$record->email" maxlength="150" placeholder="Email" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="phone" :label="$record::label('phone')" :value="$record->phone" maxlength="100" placeholder="Phone" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="mobile" :label="$record::label('mobile')" :value="$record->mobile" maxlength="100" placeholder="Mobile" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="address" :label="$record::label('address')" :value="$record->address" maxlength="100" placeholder="Address" />
    </div>
</div>
