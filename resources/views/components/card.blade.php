@props(['icon' => null, 'title' => null, 'flush' => false])
{{-- Replaces SmartAdmin's "jarviswidget" box --}}
<div {{ $attributes->class(['card mb-4']) }}>
    @if ($title || isset($tools))
        <div class="card-header">
            <h3 class="card-title">
                @if ($icon)
                    <i class="{{ $icon }}"></i>
                @endif
                {{ $title }}
            </h3>
            @isset($tools)
                <div class="card-tools">{{ $tools }}</div>
            @endisset
        </div>
    @endif
    <div @class(['card-body', 'p-0' => $flush])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
