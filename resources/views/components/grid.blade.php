@props(['grid', 'columns', 'id', 'route' => null, 'emptyText' => 'No result found.'])
{{--
    Port of Yii's CGridView. Columns:
      ['name' => 'full_name']                     sortable data column with a text filter
      ['name' => 'status', 'filter' => [1 => 'Active', ...]]   select filter (array or Collection; searchable above 10 options)
      ['name' => 'x', 'filter' => false]          no filter
      ['name' => 'x', 'value' => fn ($row) => ..., 'raw' => true]   custom cell (raw = unescaped HTML)
      ['header' => 'Actions', 'buttons' => ['update', 'delete']]    CButtonColumn, links to "{route}.{button}"
    Sorting, paging and filtering are handled by resources/js/grid.js.
--}}
@php
    $hasFilters = collect($columns)->contains(fn ($c) => isset($c['name']) && ($c['filter'] ?? 'text') !== false);
    $buttonIcons = [
        'view' => ['btn-info', 'fa fa-eye', 'View'],
        'update' => ['btn-primary', 'fa fa-pencil', 'Update'],
        'delete' => ['btn-danger', 'fa fa-times', 'Delete'],
    ];
@endphp
<div id="{{ $id }}" class="grid-view" data-grid data-grid-prefix="{{ $grid->prefix }}" data-grid-url="{{ request()->fullUrl() }}">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @php $name = $column['name'] ?? null; @endphp
                        <th @class(['button-column' => isset($column['buttons'])])>
                            @if ($name !== null && ($column['sortable'] ?? true) && $grid->isSortable($name))
                                <a href="{{ $grid->sortUrl($name) }}" data-grid-link @class([
                                    'sort-link',
                                    'asc' => $grid->sortAttribute === $name && ! $grid->sortDescending,
                                    'desc' => $grid->sortAttribute === $name && $grid->sortDescending,
                                ])>{{ $column['header'] ?? $grid->label($name) }}</a>
                            @else
                                {{ $column['header'] ?? ($name !== null ? $grid->label($name) : '') }}
                            @endif
                        </th>
                    @endforeach
                </tr>
                @if ($hasFilters)
                    <tr class="filters">
                        @foreach ($columns as $column)
                            @php
                                $name = $column['name'] ?? null;
                                $filter = $name === null ? false : ($column['filter'] ?? 'text');
                            @endphp
                            <td>
                                @if ($filter === 'text')
                                    <input type="text" class="form-control form-control-sm" name="{{ $grid->filterName($name) }}" value="{{ $grid->filterValue($name) }}">
                                @elseif (is_iterable($filter))
                                    <select @class(["form-select form-select-sm", "js-searchable" => count($filter) > 10]) name="{{ $grid->filterName($name) }}">
                                        <option value=""></option>
                                        @foreach ($filter as $value => $text)
                                            <option value="{{ $value }}" @selected($grid->filterValue($name) === (string) $value)>{{ $text }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($filter instanceof \Illuminate\Contracts\Support\Htmlable)
                                    {{ $filter }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse ($grid->rows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            @if (isset($column['buttons']))
                                <td class="button-column">
                                    @foreach ($column['buttons'] as $button)
                                        @if ($button instanceof \Closure)
                                            {!! $button($row) !!}
                                        @else
                                            @php [$btnClass, $btnIcon, $btnTitle] = $buttonIcons[$button]; @endphp
                                            <a href="{{ route($route.'.'.$button, $row->getKey()) }}" class="btn btn-sm {{ $btnClass }}" title="{{ $btnTitle }}" @if ($button === 'delete') data-grid-delete @endif><i class="{{ $btnIcon }}"></i></a>
                                        @endif
                                    @endforeach
                                </td>
                            @else
                                @php
                                    $cell = isset($column['value']) ? $column['value']($row) : data_get($row, $column['name']);
                                @endphp
                                <td class="{{ $column['class'] ?? '' }}">
                                    @if ($column['raw'] ?? false)
                                        {!! $cell !!}
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="empty"><span class="empty">{{ $emptyText }}</span></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-grid.pager :paginator="$grid->rows" />
</div>
