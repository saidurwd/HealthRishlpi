<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Version and build information for the About and System Health pages.
 * Deploys write release.json (commit, build time); in development the
 * commit comes from .git.
 */
class About
{
    public static function version(): string
    {
        return (string) config('app.version');
    }

    /**
     * @return array{commit: ?string, built_at: ?string}
     */
    public static function release(): array
    {
        $file = base_path('release.json');
        if (is_file($file)) {
            $release = json_decode((string) file_get_contents($file), true) ?: [];

            return ['commit' => $release['commit'] ?? null, 'built_at' => $release['built_at'] ?? null];
        }

        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return ['commit' => null, 'built_at' => null];
        }

        $ref = trim((string) file_get_contents($head));
        if (str_starts_with($ref, 'ref: ')) {
            $refFile = base_path('.git/'.substr($ref, 5));
            $ref = is_file($refFile) ? trim((string) file_get_contents($refFile)) : null;
        }

        return ['commit' => $ref ?: null, 'built_at' => null];
    }

    /**
     * The newest $count releases of CHANGELOG.md as HTML.
     */
    public static function changes(int $count = 3): string
    {
        $file = base_path('CHANGELOG.md');
        if (! is_file($file)) {
            return '';
        }

        $sections = preg_split('/^(?=## )/m', (string) file_get_contents($file)) ?: [];
        $releases = array_values(array_filter($sections, fn ($section) => str_starts_with($section, '## ')));

        return Str::markdown(implode("\n", array_slice($releases, 0, $count)), ['html_input' => 'escape']);
    }
}
