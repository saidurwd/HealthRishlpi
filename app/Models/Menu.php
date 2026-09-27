<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Sidebar navigation entry (`os_menu`), up to three levels deep.
 *
 * @property int $id
 * @property int $parent
 * @property string $title
 * @property string|null $controller
 * @property string $url
 * @property string|null $icon
 * @property int|null $ordering
 * @property int $status
 * @property string|null $group comma-separated os_user_group ids (informational only)
 */
#[Table('menu', timestamps: false)]
#[Fillable(['parent', 'title', 'controller', 'url', 'icon', 'ordering', 'status', 'group'])]
class Menu extends LegacyModel
{
    // Options of the tinyint `status` column (also os_user.status, os_acl_controller.status)
    public const ACTIVE_STATUSES = ['0' => 'Inactive', '1' => 'Active'];

    protected $attributes = ['parent' => 0, 'status' => 1];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'title' => 'Title',
            'controller' => 'Controller',
            'url' => 'Url',
            'icon' => 'Icon',
            'ordering' => 'Ordering',
            'status' => 'Status',
            'group' => 'Groups',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:150'],
            'url' => ['required', 'max:100'],
            'parent' => ['integer'],
            'ordering' => ['integer'],
            'status' => ['integer'],
            'group' => ['max:250'],
            'controller' => ['max:50'],
            'icon' => ['max:50'],
        ];
    }

    /** @return BelongsTo<self, $this> */
    public function parentRow(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent');
    }

    /**
     * Options for the Parent dropdown (Menu::get_menu_new()): active top-level
     * items and three levels below them, each level prefixed with the arrow
     * the Yii app used. 0 ("--please select--") makes a top-level item.
     *
     * @return array<int, string>
     */
    public static function parentOptions(): array
    {
        $rows = static::query()->where('status', 1)->orderBy('ordering')->orderBy('title')->get(['id', 'parent', 'title'])->groupBy('parent');
        $arrows = [1 => "\u{21DB}", 2 => "\u{21D2}", 3 => "\u{2192}"];
        $options = [0 => '--please select--'];

        $walk = function (int $parentId, int $level) use (&$walk, &$options, $rows, $arrows) {
            foreach ($rows[$parentId] ?? [] as $row) {
                $options[$row->id] = $arrows[$level].$row->title;
                if ($level < 3) {
                    $walk($row->id, $level + 1);
                }
            }
        };

        // The top level is ordered by title alone (Yii: ORDER BY parent, title)
        foreach (static::query()->where('parent', 0)->where('status', 1)->orderBy('title')->get(['id', 'title']) as $root) {
            $options[$root->id] = $root->title;
            $walk($root->id, 1);
        }

        return $options;
    }

    /**
     * Active menu rows as a nested tree, ordered like Menu::get_menu().
     *
     * Each node gets `children` (Collection) and `active` (bool). A leaf is
     * active when its url is "/<controller>/<action>" for the current route,
     * and a parent is active when any descendant is.
     *
     * @return Collection<int, Menu>
     */
    public static function tree(?string $controller, ?string $action): Collection
    {
        $rows = static::query()
            ->where('status', 1)
            ->orderBy('ordering')
            ->orderBy('title')
            ->get()
            ->groupBy('parent');

        $build = function (int $parentId, int $depth) use (&$build, $rows, $controller, $action): Collection {
            return ($rows[$parentId] ?? collect())->map(function (Menu $item) use ($build, $depth, $controller, $action) {
                $item->children = $depth < 3 ? $build($item->id, $depth + 1) : collect();

                $pieces = explode('/', (string) $item->url);
                $item->active = ($controller !== null && ($pieces[1] ?? null) === $controller && ($pieces[2] ?? null) === $action)
                    || $item->children->contains('active', true);

                return $item;
            });
        };

        return $build(0, 1);
    }

    /**
     * Href for the menu link. Parents link to "#", like the Yii menu did.
     */
    public function href(): string
    {
        if ($this->children->isNotEmpty() || $this->url === '#' || $this->url === '') {
            return '#';
        }

        return url($this->url);
    }
}
