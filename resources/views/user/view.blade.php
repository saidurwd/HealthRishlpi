@extends('layouts.app')

@section('title', $record->full_name.' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-list-alt" title="User" :subtitle="$record->full_name" :breadcrumbs="['Users' => route('user.admin'), $record->full_name]" />
@endsection

@section('content')
    <x-card icon="fa fa-tasks" :title="$record->full_name">
        <x-slot:tools>
            <a href="{{ route('user.create') }}" class="btn btn-sm btn-primary" title="New"><i class="fa fa-plus"></i></a>
        </x-slot:tools>
        <x-detail-view :rows="[
            $record::label('id') => $record->id,
            $record::label('full_name') => $record->full_name,
            $record::label('username') => $record->username,
            $record::label('email') => $record->email,
            $record::label('register_date') => \App\Support\YiiFormat::dateTime($record->register_date),
            $record::label('lastvisit') => \App\Support\YiiFormat::dateTime($record->lastvisit),
            $record::label('group_id') => $record->group0?->title,
            $record::label('department') => $record->department0?->title,
            $record::label('status') => $record->status0?->title,
        ]" />
    </x-card>
@endsection
