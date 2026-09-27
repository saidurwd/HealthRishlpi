<div class="row">
    <div class="col-md-6">
        <x-form.input name="full_name" :label="$record::label('full_name')" :value="$record->full_name" maxlength="150" placeholder="Name" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="username" :label="$record::label('username')" :value="$record->username" maxlength="150" placeholder="Username" required />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.input name="email" :label="$record::label('email')" :value="$record->email" maxlength="150" placeholder="Email" required />
    </div>
</div>
@unless ($record->exists)
    <div class="row">
        <div class="col-md-6">
            <x-form.input name="password" type="password" :label="$record::label('password')" maxlength="150" placeholder="Password" required />
        </div>
    </div>
@endunless
<div class="row">
    <div class="col-md-6">
        <x-form.select name="group_id" :label="$record::label('group_id')" :options="$groups" :value="$record->group_id" empty="-select-" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="department" :label="$record::label('department')" :options="$departments" :value="$record->department" empty="-select-" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <x-form.select name="status" :label="$record::label('status')" :options="\App\Models\Menu::ACTIVE_STATUSES" :value="$record->status" />
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="photo" class="form-label">{{ $record::label('photo') }}</label>
            <input type="file" id="photo" name="photo" accept="image/*" @class(['form-control', 'is-invalid' => $errors->has('photo')])>
            @error('photo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if ($record->exists)
                <img src="{{ $record->photoUrl() }}" alt="{{ $record->full_name }}" width="50" class="mt-2">
            @endif
        </div>
    </div>
</div>
