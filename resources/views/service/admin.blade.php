@extends('crud.admin')

@section('tools')
    <a href="{{ route('report.allserviceprint') }}" target="_blank" class="btn btn-sm btn-info" title="Print"><i class="fa fa-print"></i> PRINT</a>
@endsection

@section('grid')
    <x-grid id="service-grid" route="service" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => $parents],
        ['name' => 'title', 'value' => fn ($row) => $row->fullPath(), 'raw' => true],
        ['name' => 'rate'],
        ['name' => 'rate_status', 'filter' => ['Auto' => 'Auto', 'Manual' => 'Manual']],
        ['name' => 'discount', 'filter' => ['No' => 'No', 'Yes' => 'Yes']],
        ['name' => 'service_type', 'filter' => ['Consultation' => 'Consultation', 'Service' => 'Service']],
        ['name' => 'service_grade', 'value' => fn ($row) => $grades[$row->service_grade] ?? null, 'filter' => $grades],
        ['name' => 'ordering'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
