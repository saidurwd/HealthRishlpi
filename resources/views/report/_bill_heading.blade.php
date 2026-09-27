{{ $name }}: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
@if ($f['category_new'] && $name !== 'Medicine Bill Report') ; Category: {{ \App\Models\PatientCategoryNew::query()->whereKey($f['category_new'])->value('alias') }} @endif
{{-- The medicine print tested the sub category for both lines --}}
@if ($f['category'] && $name === 'Medicine Bill Report') ; Category: {{ \App\Models\PatientCategoryNew::query()->whereKey($f['category_new'])->value('alias') }} @endif
@if ($f['category']) ; Sub Category: {{ \App\Models\PatientCategory::query()->whereKey($f['category'])->value('alias') }} @endif
@if ($f['service']) ; Service: {{ $serviceTitle }} @endif
@if ($f['status']) ; Status: {{ $f['status'] }} @endif
