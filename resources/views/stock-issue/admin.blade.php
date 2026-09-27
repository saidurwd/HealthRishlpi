@extends('layouts.app')

@section('title', 'Stock Issues')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Issues" subtitle="Manage" :breadcrumbs="['Stock Issues' => route('stockIssue.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Stock Issues" flush>
        <x-slot:tools>
            <a href="{{ route('stockIssue.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        <x-grid id="stock-issue-parent-grid" :grid="$grid" :columns="[
            ['header' => 'References', 'value' => fn ($row) => $row->references()],
            ['name' => 'issue_number'],
            ['name' => 'issue_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->issue_date)],
            ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
            ['name' => 'total_amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
            ['name' => 'issue_by', 'value' => fn ($row) => $row->issueBy?->full_name, 'filter' => $users],
            ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::STOCK_ISSUE), 'filter' => $statuses],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('stockIssue.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockIssue.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockIssue.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                fn ($row) => $row->canSpecialEdit() ? '<a href=\''.e(route('stockIssue.edit', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Special Edit\'><i class=\'fa fa-edit\'></i></a>' : '',
                fn ($row) => '<a href=\''.e(route('stockIssue.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
