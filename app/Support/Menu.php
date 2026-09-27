<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Builds the sidebar from config('menu') for one user: drops items they
 * lack the `can` permission for (and parents and section headers left
 * empty), and marks the item of the current route active along with its
 * parents.
 */
class Menu
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{header: string}|array{text: string, href: string, icon: ?string, active: bool, submenu: array<int, mixed>}>
     */
    public static function build(array $items, ?Authorizable $user, ?string $currentRoute): array
    {
        $built = [];

        foreach ($items as $item) {
            // Section headers are kept only when something visible follows them
            if (isset($item['header'])) {
                $built[] = ['header' => $item['header']];

                continue;
            }

            if (isset($item['can']) && ! $user?->can($item['can'])) {
                continue;
            }

            $submenu = self::build($item['submenu'] ?? [], $user, $currentRoute);
            if (isset($item['submenu']) && $submenu === []) {
                continue;
            }

            $route = $item['route'] ?? null;
            $built[] = [
                'text' => $item['text'],
                'href' => match (true) {
                    $submenu !== [] => '#',
                    $route !== null => route($route),
                    default => url($item['url'] ?? '#'),
                },
                'icon' => $item['icon'] ?? null,
                'active' => ($route !== null && $route === $currentRoute) || collect($submenu)->contains('active', true),
                'submenu' => $submenu,
            ];
        }

        return array_values(array_filter($built, fn ($entry, $i) => ! isset($entry['header'])
            || (isset($built[$i + 1]) && ! isset($built[$i + 1]['header'])), ARRAY_FILTER_USE_BOTH));
    }
}
