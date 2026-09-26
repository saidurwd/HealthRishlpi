<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
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
 */
#[Table('menu', timestamps: false)]
class Menu extends Model
{
    protected $guarded = ['id'];

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
