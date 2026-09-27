@props(['item'])
@php($hasChildren = $item['submenu'] !== [])
<li @class(['nav-item', 'menu-open' => $hasChildren && $item['active']])>
    <a href="{{ $item['href'] }}" @class(['nav-link', 'active' => $item['active']])>
        <i class="nav-icon {{ $item['icon'] ?? 'fa-regular fa-circle' }}"></i>
        <p>
            {{ $item['text'] }}
            @if ($hasChildren)
                <i class="nav-arrow fa fa-angle-right"></i>
            @endif
        </p>
    </a>
    @if ($hasChildren)
        <ul class="nav nav-treeview">
            @foreach ($item['submenu'] as $child)
                <x-menu-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>
