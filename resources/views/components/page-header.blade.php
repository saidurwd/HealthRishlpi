@props(['icon' => 'fa fa-home', 'title', 'subtitle' => null, 'breadcrumbs' => []])
{{--
    Page title plus breadcrumbs (CBreadcrumbs). Breadcrumbs are
    ['Label' => url, ..., 'Current page'], with the Home link added first.
--}}
<div class="row">
    <div class="col-sm-6">
        <h3 class="mb-0">
            <i class="{{ $icon }} fa-fw"></i>
            {{ $title }}
            @if ($subtitle)
                <small class="text-body-secondary fs-6">&gt; {{ $subtitle }}</small>
            @endif
        </h3>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ url('/dashboard/index') }}">Home</a></li>
            @foreach ($breadcrumbs as $label => $link)
                @if (is_int($label))
                    <li class="breadcrumb-item active" aria-current="page">{{ $link }}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $link }}">{{ $label }}</a></li>
                @endif
            @endforeach
        </ol>
    </div>
</div>
