{{-- The prescription printouts. $full adds the diagnosis, the pharmacy order and the invoices raised; without it the page is left for handwriting (preblank). --}}
<x-report-header>
    <div style="width:200px;text-align:right;">
        {{ $prescription->pre_number }}<br>
        PID: {{ $record->pat_id }}<br>
        REF#: {{ $record->ref_no }}<br>
        CATEGORY: {{ $record->category0?->alias }}
    </div>
</x-report-header>
<div style="border-bottom:1px solid #666;"></div>
<div class="d-flex" style="margin-top:10px;font-size:16px;">
    <div style="width:230px;"><strong>Name:</strong> {{ $record->name }}</div>
    <div style="width:180px;"><strong>Age:</strong> {{ $record->ageText() }}</div>
    <div style="width:90px;"><strong>Sex:</strong> {{ $record->sex }}</div>
    <div><strong>Date:</strong> {{ date('M j, Y') }}</div>
</div>
<div class="d-flex" style="margin-top:10px;font-size:16px;">
    <div style="width:400px;"><strong>Address:</strong> {{ $record->fullAddress() }}</div>
    <div><strong>Diagnosis:</strong> {{ $full ? $prescription->diagnosis0?->title : '' }}</div>
</div>
<div class="d-flex" style="margin-top:20px;">
    <div style="width:250px;border-right:1px solid #999;">
        @foreach (['cc' => 'C/C', 'oe' => 'O/E', 'bp' => 'B/P', 'pulse' => 'Pulse', 'temp' => 'Temp', 'advice' => 'Advice', 'admission' => 'Admission'] as $field => $caption)
            <h3 style="margin-bottom:50px;">{{ $caption }}: {{ $prescription->{$field} }}</h3>
        @endforeach
    </div>
    <div style="width:425px;margin-left:10px;">
        <h1 style="padding-left:10px;">Rx</h1>
        <p>{{ $prescription->rx }}</p>
        @if ($full)
            <h5 style="text-align:center;">PHARMACY ORDER</h5>
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr><th>SL#</th><th>Medicine</th><th>Instruction</th><th>No of Days</th></tr>
                </thead>
                <tbody>
                    @foreach ($prescription->medicines()->orderByDesc('created_on')->get() as $medicine)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $medicine->product }}</td>
                            <td>{{ $medicine->instruction }}</td>
                            <td class="text-center">{{ $medicine->no_of_days }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>
                @foreach ($invoices as $invoice)
                    {{ $invoice->invoice_number }}
                    <ul>
                        @foreach ($invoice->lines->where('servicetype', 'Medicine') as $line)
                            <li>{{ $line->item0?->title }} {{ round((float) $line->quantity) }}{{ $line->uom() }}</li>
                        @endforeach
                    </ul>
                    <ul>
                        @foreach ($invoice->lines->where('servicetype', 'Service') as $line)
                            <li>{{ $line->service0?->title }} {{ round((float) $line->quantity) }}unit</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        @endif
    </div>
</div>
<div style="border-top:1px solid #666;padding-top:20px;color:#333;font-size:14px;">
    {{ config('legacy.adminAddress') }}<br>
    Email: {{ config('legacy.rishilpiEmail') }}<br>
    Physician visit time: {{ config('legacy.physician_visit_time') }}<br>
</div>
