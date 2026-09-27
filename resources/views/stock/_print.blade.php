{{--
    Printed requisition / issue / transfer: organisation, status block,
    lines ($columns: header => fn($row)), comments and total.
--}}
<div class="d-flex justify-content-between">
    <div>
        <div class="fs-4">{{ config('legacy.adminName') }}</div>
        {{ config('legacy.adminAddress') }}
    </div>
    <h1 class="fw-normal">{{ $title }}</h1>
</div>
<div class="row mt-2">
    <div class="col-8"></div>
    <div class="col-4">
        @foreach ($facts as $label => $value)
            <div class="d-flex justify-content-between"><strong>{{ $label }} :</strong> <span>{{ $value }}</span></div>
        @endforeach
        <div class="bg-dark text-white p-2 mt-2 d-flex justify-content-between"><span>Total Amount :</span> <span>{{ $total }}</span></div>
    </div>
</div>
<table class="table table-hover mt-3">
    <thead><tr>@foreach ($columns as $header => $value)<th>{{ $header }}</th>@endforeach</tr></thead>
    <tbody>
        @foreach ($lines->rows as $row)
            <tr>@foreach ($columns as $value)<td>{{ $value($row) }}</td>@endforeach</tr>
        @endforeach
    </tbody>
</table>
<div class="row">
    <div class="col-7">
        <h5>Comments</h5>
        <p>{{ $parent->comments }}</p>
    </div>
    <div class="col-5 text-end">
        <h3><strong>Total: <span class="text-success">{{ $total }}</span></strong></h3>
    </div>
</div>
<p class="text-body-secondary">Generated on {{ date('l jS \of F Y h:i:s A') }}</p>
