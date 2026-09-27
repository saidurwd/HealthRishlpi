@extends('layouts.report')

@section('content')
    <x-report-header />
    <div style="border-bottom:1px solid #666;"></div>
    <div style="background-color:#666;color:#FFF;text-align:center;font-size:16px;text-transform:uppercase;padding:2px 0;">Health Card</div>
    <div style="margin-top:20px;text-transform:capitalize;">
        <div class="d-flex"><div style="width:220px;"><strong>Date:</strong> {{ date('F j, Y', strtotime((string) $record->created_on)) }}</div><div><strong>No.:</strong> {{ $record->pat_id }}</div></div>
        <div><strong>Name:</strong> {{ $record->name }}</div>
        <div class="d-flex"><div style="width:220px;"><strong>Sex:</strong> {{ $record->sex }}</div><div><strong>Age:</strong> {{ $record->ageText() }}</div></div>
        <div><strong>Address:</strong> {{ $record->fullAddress() }}</div>
    </div>
    <div style="border-top:1px solid #666;padding-top:10px;color:#333;margin-top:10px;font-size:14px;">
        {{ config('legacy.adminAddress') }}<br>
        Email: {{ config('legacy.rishilpiEmail') }}<br>
        Physician visit time: {{ config('legacy.physician_visit_time') }}<br>
    </div>
@endsection
