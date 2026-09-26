<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Batch" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="manufacturing" :label="$record::label('manufacturing')" :value="$record->manufacturing" maxlength="50" placeholder="Manufacturing" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="expiry" :label="$record::label('expiry')" :value="$record->expiry" maxlength="50" placeholder="Expiry" required />
    </div>
</div>
