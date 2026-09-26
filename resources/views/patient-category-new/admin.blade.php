@extends('crud.admin')

@section('grid')
    {{-- The Yii grid printed the full path HTML-escaped (icons showed as text); it is rendered here --}}
    <x-grid id="patient-category-new-grid" route="patientCategoryNew" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => $parents],
        ['name' => 'title', 'value' => fn ($row) => $row->fullPath(), 'raw' => true],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
