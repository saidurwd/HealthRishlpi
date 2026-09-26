<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Port of the update_path()/update_alias()/get_full_path() helpers shared by
 * Department, ProductCategory, PatientCategory, PatientCategoryNew, Service
 * and Store. `path` is "0.<id>" for a root and "<parent path>.<id>" below it;
 * `alias` is the "/"-joined titles.
 *
 * @property int|null $parent
 * @property string|null $path
 * @property string|null $alias
 * @property string $title
 */
trait HasTreePath
{
    /**
     * @return BelongsTo<static, $this>
     */
    public function parentRow(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent');
    }

    /**
     * Recompute path and alias from the parent (runs after every create/update).
     * Children are not updated, as in the Yii app.
     */
    public function updatePath(): void
    {
        $parent = $this->parent ? static::query()->find($this->parent) : null;

        $this->path = $parent ? $parent->path.'.'.$this->id : '0.'.$this->id;
        $this->alias = $parent ? $parent->alias.'/'.$this->title : $this->title;
        $this->saveQuietly();
    }

    /**
     * Titles along the path joined with an arrow icon (get_full_path()). HTML.
     */
    public function fullPath(): string
    {
        $ids = array_values(array_filter(explode('.', (string) $this->path), fn ($id) => (int) $id > 0));
        $titles = static::query()->whereKey($ids)->pluck('title', 'id');

        return collect($ids)
            ->map(fn ($id) => e($titles[$id] ?? ''))
            ->implode(' <i class="fa fa-angle-double-right text-info"></i> ');
    }

    /**
     * Top-level rows (parent NULL or 0) ordered by path, as id => title.
     *
     * @return Collection<int, string>
     */
    public static function roots(): Collection
    {
        return static::query()
            ->where(fn ($query) => $query->whereNull('parent')->orWhere('parent', 0))
            ->orderBy('path')
            ->pluck('title', 'id');
    }

    /**
     * Roots (parent NULL or 0) and up to $depth levels below them, each level
     * ordered by path, as id => indented title for a dropdown
     * (ProductCategory::getProductCategory()).
     *
     * @return array<int, string>
     */
    public static function treeOptions(int $depth = 4): array
    {
        $byParent = static::query()->orderBy('path')->get(['id', 'parent', 'title'])->groupBy(fn ($row) => (int) $row->parent);
        $options = [];

        $walk = function (Collection $rows, int $level) use (&$walk, &$options, $byParent, $depth) {
            foreach ($rows as $row) {
                $options[$row->id] = str_repeat("\u{00A0}", 6 * $level).$row->title;
                if ($level + 1 < $depth) {
                    $walk($byParent->get($row->id, collect()), $level + 1);
                }
            }
        };
        $walk($byParent->get(0, collect()), 0);

        return $options;
    }
}
