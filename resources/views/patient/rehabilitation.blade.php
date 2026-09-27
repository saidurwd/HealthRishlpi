@extends('layouts.report')

@section('title', 'Rehabilitation Services')
@section('print-delay', 5000)

@push('head')
    <style>body { font-size: 13px; line-height: 25px; text-transform: capitalize; }</style>
@endpush

@section('content')
    <x-report-header />
    <hr style="margin-top:5px;border-top:1px solid #666;">
    <h2 style="text-align:center;text-decoration:underline;">Patient Particular</h2>
    <table style="width:100%;border:0;">
        <tr>
            <td style="width:50%;">
                <strong>DATE:</strong> {{ date('M j, Y') }}<br>
                <strong>NAME:</strong> {{ $record->name }}<br>
                <strong>ADDRESS:</strong> {{ $record->address }}<br>
                <strong>No.:</strong> {{ $record->pat_id }}<br>
                <strong>Ref. No:</strong> {{ $record->ref_no }}
            </td>
            <td>
                <strong>Age:</strong> {{ $record->ageText() }}<br>
                <strong>SEX:</strong> {{ $record->sex }}<br>
                <strong>MOBILE NO:</strong> {{ $record->mobile }}<br>
                <strong>REFERRED BY:</strong> {{ $record->referred }}
            </td>
        </tr>
    </table>
    <hr style="margin-top:10px;border-top:1px solid #666;">
    <div style="margin-top:30px;padding:5px;text-align:left;">
        <h5 style="margin-bottom:50px;">Chief Complaints:</h5>
        <h5>Services:</h5>
        <ol>
            <li>Orthopedic and Neurology Physiotherapy (Adult)</li>
            <li>Pediatric Physiotherapy</li>
            <li>Occupational Therapy</li>
            <li>Special Education</li>
            <li>Combined (PT + OT + Sp. Ed.)</li>
            <li>CBR (Assasuni; Tala; Kalaroa; Keshabpur and Khulna Center)</li>
        </ol>
        <h5 style="margin-bottom:50px;margin-top:40px;">Referred to:</h5>
        <h5 style="margin-top:30px;">Advice:</h5>
        <h5 style="margin-top:30px;text-align:right;">Signature</h5>
    </div>
    <hr style="margin-top:85px;border-top:1px solid #666;">
    <div style="margin-top:20px;">
        <p style="color:#999;">
            {{ config('legacy.adminAddress') }}<br>
            <span style="text-transform:lowercase;">Email: {{ config('legacy.rishilpiEmail') }}</span><br>
            Physician visit time: {{ config('legacy.physician_visit_time_2') }}
        </p>
    </div>
@endsection
