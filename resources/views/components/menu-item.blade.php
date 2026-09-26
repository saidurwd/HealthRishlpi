@props(['item'])
@php
    $hasChildren = $item->children->isNotEmpty();
    // os_menu icons carry SmartAdmin sizing (fa-lg); the sidebar sizes icons itself
    $icon = trim(str_replace('fa-lg', '', (string) $item->icon)) ?: 'fa-regular fa-circle';
@endphp
<li @class(['nav-item', 'menu-open' => $hasChildren && $item->active])>
    <a href="{{ $item->href() }}" @class(['nav-link', 'active' => $item->active])>
        <i class="nav-icon {{ $icon }}"></i>
        <p>
            {{ $item->title }}
            @if ($hasChildren)
                <i class="nav-arrow fa fa-angle-right"></i>
            @endif
        </p>
    </a>
    @if ($hasChildren)
        <ul class="nav nav-treeview">
            @foreach ($item->children as $child)
                <x-menu-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>
