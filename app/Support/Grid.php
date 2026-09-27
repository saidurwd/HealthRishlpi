<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Server side of the Yii CGridView port: filtering, sorting and paging a
 * query from the request, using the same query-string layout Yii used
 * (`Unit[full_name]=kg`, `Unit_sort=full_name.desc`, `Unit_page=2`).
 *
 * Render the result with the <x-grid> Blade component.
 */
class Grid
{
    /** @var array<string, mixed> */
    public array $filters;

    public ?string $sortAttribute = null;

    public bool $sortDescending = false;

    public LengthAwarePaginator $rows;

    /** @var array<int, string> */
    private array $sortable = [];

    /** @var array<string, string> sort attribute => column/expression it orders by */
    private array $sortColumns = [];

    /** @var array<int, array{0: string, 1: string}> */
    private array $defaultOrder = [];

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        public Builder $query,
        public string $prefix,
        public string $model,
        public Request $request,
    ) {
        $filters = $request->query($prefix, []);
        $this->filters = is_array($filters) ? $filters : [];
    }

    /**
     * @param  Builder<covariant Model>  $query
     */
    public static function for(Builder $query, ?string $prefix = null, ?Request $request = null): self
    {
        $model = $query->getModel()::class;

        return new self($query, $prefix ?? class_basename($model), $model, $request ?? request());
    }

    /**
     * Port of CDbCriteria::compare() for one filter attribute. With $partial,
     * plain values match with LIKE %value%. Values may start with an operator
     * (<>, <=, >=, <, >, =) exactly as in Yii.
     */
    public function compare(string $attribute, bool $partial = false, ?string $column = null): self
    {
        $value = $this->filters[$attribute] ?? null;
        $column ??= $this->query->getModel()->qualifyColumn($attribute);

        self::applyCompare($this->query, $column, $value, $partial);
        $this->sortable($attribute, $column);

        return $this;
    }

    /**
     * @param  Builder<Model>|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyCompare($query, string $column, mixed $value, bool $partial = false): void
    {
        if (is_array($value)) {
            if ($value !== []) {
                $query->whereIn($column, $value);
            }

            return;
        }

        $value = (string) $value;
        if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/s', $value, $matches) !== 1) {
            return;
        }
        [, $op, $value] = $matches;

        if ($value === '') {
            return;
        }

        if ($partial) {
            $like = '%'.strtr($value, ['%' => '\%', '_' => '\_', '\\' => '\\\\']).'%';
            if ($op === '') {
                $query->where($column, 'like', $like);

                return;
            }
            if ($op === '<>') {
                $query->where($column, 'not like', $like);

                return;
            }
        } elseif ($op === '') {
            $op = '=';
        }

        $query->where($column, $op, $value);
    }

    /**
     * Allow sorting on an attribute that has no filter.
     */
    public function sortable(string $attribute, ?string $column = null): self
    {
        $this->sortable[] = $attribute;
        $this->sortColumns[$attribute] = $column ?? $this->query->getModel()->qualifyColumn($attribute);

        return $this;
    }

    public function isSortable(string $attribute): bool
    {
        return in_array($attribute, $this->sortable, true);
    }

    /**
     * Order used when the request has no sort (CSort::defaultOrder).
     */
    public function defaultOrder(string $column, string $direction = 'asc'): self
    {
        $this->defaultOrder[] = [$column, $direction];

        return $this;
    }

    public function paginate(int $pageSize): self
    {
        $sort = (string) $this->request->query($this->sortParam(), '');
        $attribute = str_ends_with($sort, '.desc') ? substr($sort, 0, -5) : $sort;

        if ($attribute !== '' && $this->isSortable($attribute)) {
            $this->sortAttribute = $attribute;
            $this->sortDescending = str_ends_with($sort, '.desc');
            $this->query->orderBy($this->sortColumns[$attribute], $this->sortDescending ? 'desc' : 'asc');
        } else {
            foreach ($this->defaultOrder as [$column, $direction]) {
                $this->query->orderBy($column, $direction);
            }
        }

        $this->rows = $this->query
            ->paginate($pageSize, ['*'], $this->pageParam())
            ->withQueryString();

        return $this;
    }

    public function sortParam(): string
    {
        return $this->prefix.'_sort';
    }

    public function pageParam(): string
    {
        return $this->prefix.'_page';
    }

    public function filterName(string $attribute): string
    {
        return $this->prefix.'['.$attribute.']';
    }

    public function filterValue(string $attribute): string
    {
        $value = $this->filters[$attribute] ?? '';

        return is_array($value) ? '' : (string) $value;
    }

    /**
     * Header link URL: first click sorts ascending, clicking the sorted
     * column again flips the direction (CSort behaviour). Paging resets.
     */
    public function sortUrl(string $attribute): string
    {
        $descending = $this->sortAttribute === $attribute && ! $this->sortDescending;

        $query = array_merge($this->request->query(), [
            $this->sortParam() => $attribute.($descending ? '.desc' : ''),
        ]);
        unset($query[$this->pageParam()]);

        return $this->request->url().'?'.http_build_query($query);
    }

    public function label(string $attribute): string
    {
        return method_exists($this->model, 'label')
            ? $this->model::label($attribute)
            : ucwords(str_replace('_', ' ', $attribute));
    }
}
