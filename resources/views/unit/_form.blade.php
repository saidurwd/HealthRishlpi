<div class="row">
    <div class="col-md-6">
        <x-form.input name="full_name" :label="$record::label('full_name')" :value="$record->full_name" maxlength="150" placeholder="Full Name" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="formal_name" :label="$record::label('formal_name')" :value="$record->formal_name" maxlength="150" placeholder="Formal Name" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="decimal_place" :label="$record::label('decimal_place')" :value="$record->decimal_place" maxlength="4" placeholder="Decimal Place" />
    </div>
</div>
