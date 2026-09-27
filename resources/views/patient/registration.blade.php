@extends('layouts.report')

@section('title', 'Patient Registration Form')
@section('print-delay', 5000)

@push('head')
    <style>body { font-size: 13px; line-height: 30px; text-transform: capitalize; }</style>
@endpush

@section('content')
    <x-report-header style="font-size:14px;" />
    <hr style="margin-top:10px;border-top:1px solid #666;">
    <h2 style="text-align:center;text-decoration:underline;">Patient Registration Form</h2>
    <div style="margin-top:30px;padding:5px;text-align:left;font-size:14px;">
        <h5>{{ $record->category_new0?->alias }}</h5>
        <table style="width:100%;border:0;">
            <tr>
                <td style="width:34%;"><strong>DATE:</strong> {{ date('M j, Y') }}</td>
                <td style="width:33%;"><strong>Ref. No:</strong> {{ $record->ref_no }}</td>
                <td style="width:33%;"><strong>Grade:</strong> {{ $record->grade0?->title }}</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="2"><strong>No.:</strong> {{ $record->pat_id }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Name of Patient:</strong> {{ $record->name }}</td>
                <td><strong>Age:</strong> {{ $record->age.' '.$record->age_type }}</td>
            </tr>
            <tr>
                <td colspan="3"><strong>Name of Husband/Father:</strong> {{ $record->emergency_name }}</td>
            </tr>
        </table>
        <table style="width:100%;border:0;">
            <tr>
                <td style="width:50%;"><strong>Village:</strong> {{ $record->village }}</td>
                <td><strong>Post Office:</strong> {{ $record->post }}</td>
            </tr>
            <tr>
                <td><strong>P.S:</strong> {{ $record->thana0?->title }}</td>
                <td><strong>Dist:</strong> {{ $record->district0?->title }}</td>
            </tr>
            <tr><td colspan="2"><strong>Contact No:</strong> {{ $record->mobile }}</td></tr>
            <tr><td colspan="2"><strong>Occupation:</strong> {{ $record->occupation }}</td></tr>
            <tr><td colspan="2"><strong>Guardian Occupation:</strong> {{ $record->guardian_occupation }}</td></tr>
            <tr><td colspan="2"><strong>No. of family member:</strong> {{ $record->no_of_family_member }}</td></tr>
            <tr><td colspan="2"><strong>Earning Member:</strong> {{ $record->earning_member }}</td></tr>
            <tr><td colspan="2"><strong>Earning Source:</strong> {{ $record->earning_source }}</td></tr>
        </table>
    </div>
    <div style="margin-top:100px;">
        <p style="color:#666;">I have read, fully understand and agree to payment, consent for treatment. I hereby declare that the Information provided above is true, correct and complete.</p>
    </div>
    <div style="margin-top:150px;">
        <table style="width:100%;border:0;">
            <tr>
                <td style="width:25%;text-align:left;border-top:1px dotted #666;">Signature of patient</td>
                <td style="width:50%;">&nbsp;</td>
                <td style="width:25%;text-align:right;border-top:1px dotted #666;">Authorized (In-Charge)</td>
            </tr>
        </table>
    </div>
@endsection
