@php $selectedGroups = explode(',', (string) (old('group') !== null ? implode(',', (array) old('group')) : $record->group)); @endphp
<div class="row">
    <div class="col-md-6">
        <x-form.select name="parent" :label="$record::label('parent')" :options="$parents" :value="$record->parent" searchable />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Menu Name" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="controller" :label="$record::label('controller')" :value="$record->controller" maxlength="150" placeholder="Controller" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="url" :label="$record::label('url')" :value="$record->url" maxlength="150" placeholder="URL" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="icon" :label="$record::label('icon')" :value="$record->icon" maxlength="150" placeholder="Icon" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="ordering" :label="$record::label('ordering')" :value="$record->ordering" maxlength="150" placeholder="Ordering" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="$record::ACTIVE_STATUSES" :value="$record->status" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="group" class="form-label">{{ $record::label('group') }}</label>
            {{-- Posts nothing when every option is cleared; the hidden field clears the column --}}
            <input type="hidden" name="group" value="">
            <select id="group" name="group[]" multiple @class(['form-select js-searchable', 'is-invalid' => $errors->has('group')])>
                @foreach ($groups as $groupId => $groupTitle)
                    <option value="{{ $groupId }}" @selected(in_array((string) $groupId, $selectedGroups, true))>{{ $groupTitle }}</option>
                @endforeach
            </select>
            @error('group')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
