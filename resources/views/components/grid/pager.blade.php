@props(['paginator', 'maxButtons' => 10])
{{-- Port of Yii's CLinkPager: first/previous, up to 10 page links, next/last; hidden with one page --}}
@php
    $pageCount = $paginator->lastPage();
    $current = $paginator->currentPage();
    $begin = max(1, $current - intdiv($maxButtons, 2));
    $end = $begin + $maxButtons - 1;
    if ($end > $pageCount) {
        $end = $pageCount;
        $begin = max(1, $end - $maxButtons + 1);
    }
@endphp
@if ($pageCount > 1)
    <nav class="grid-pager">
        <ul class="pagination pagination-sm flex-wrap">
            <li @class(['page-item', 'disabled' => $current === 1])><a class="page-link" data-grid-link href="{{ $paginator->url(1) }}">&lt;&lt; First</a></li>
            <li @class(['page-item', 'disabled' => $current === 1])><a class="page-link" data-grid-link href="{{ $paginator->url(max(1, $current - 1)) }}">&lt; Previous</a></li>
            @for ($page = $begin; $page <= $end; $page++)
                <li @class(['page-item', 'active' => $page === $current])><a class="page-link" data-grid-link href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
            @endfor
            <li @class(['page-item', 'disabled' => $current === $pageCount])><a class="page-link" data-grid-link href="{{ $paginator->url(min($pageCount, $current + 1)) }}">Next &gt;</a></li>
            <li @class(['page-item', 'disabled' => $current === $pageCount])><a class="page-link" data-grid-link href="{{ $paginator->url($pageCount) }}">Last &gt;&gt;</a></li>
        </ul>
    </nav>
@endif
