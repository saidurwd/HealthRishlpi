{{-- Logo and organisation lines printed at the top of every form --}}
<div {{ $attributes->merge(['class' => 'report-header d-flex align-items-start gap-3 mb-2']) }}>
    <img src="{{ asset('images/rishilpi_logo.png') }}" alt="Logo">
    <div class="mt-3 flex-grow-1" style="font-size: 16px;">
        {{ config('legacy.topTag') }}<br>
        {{ config('legacy.adminName') }}<br>
        {{ config('legacy.bottomTag') }}
    </div>
    {{ $slot }}
</div>
