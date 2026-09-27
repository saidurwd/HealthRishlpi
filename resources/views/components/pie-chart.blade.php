@props(['title', 'data'])
{{--
    Pie chart of label => value (the legacy report used Highcharts pies):
    slices with a legend of label, value and percentage.
--}}
@php
    $total = array_sum($data);
    $colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
    $angle = -M_PI / 2;
    $slices = [];
    foreach (array_values(array_filter($data)) as $i => $value) {
        $sweep = $total > 0 ? 2 * M_PI * $value / $total : 0;
        $end = $angle + $sweep;
        $slices[] = [
            'd' => $sweep >= 2 * M_PI - 0.0001
                ? 'M 100 0 A 100 100 0 1 1 99.99 0 Z'
                : sprintf('M 100 100 L %.3f %.3f A 100 100 0 %d 1 %.3f %.3f Z', 100 + 100 * cos($angle), 100 + 100 * sin($angle), $sweep > M_PI ? 1 : 0, 100 + 100 * cos($end), 100 + 100 * sin($end)),
            'color' => $colors[$i % count($colors)],
        ];
        $angle = $end;
    }
    $legend = array_filter($data);
@endphp
<figure class="text-center">
    <figcaption class="fw-bold mb-2">{{ $title }}</figcaption>
    @if ($total > 0)
        <svg viewBox="0 0 200 200" width="220" height="220" role="img" aria-label="{{ $title }}">
            @foreach ($slices as $slice)
                <path d="{{ $slice['d'] }}" fill="{{ $slice['color'] }}" stroke="#fff" stroke-width="1"></path>
            @endforeach
        </svg>
        <ul class="list-unstyled small text-start d-inline-block mt-2 mb-0">
            @foreach ($legend as $label => $value)
                <li><span class="d-inline-block me-1" style="width:10px;height:10px;background:{{ $colors[$loop->index % count($colors)] }}"></span><b>{{ $label }}</b>: {{ $value }} ({{ number_format($value * 100 / $total, 1) }} %)</li>
            @endforeach
        </ul>
    @else
        <p class="text-body-secondary">No data.</p>
    @endif
</figure>
