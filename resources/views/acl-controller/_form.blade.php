<div class="row">
    <div class="col-md-6">
        <x-form.input name="controller" :label="$record::label('controller')" :value="$record->controller" maxlength="150" placeholder="Controller" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="100" placeholder="Title" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="['1' => 'Yes', '0' => 'No']" :value="$record->status" />
    </div>
</div>
