<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Which deploy of the site is running, from the build.json each deploy
 * writes. The footer shows it as "Last update", so anyone can tell at a
 * glance whether a change has gone live.
 */
class Build
{
    /** @return array{revision: ?string, updated_at: ?CarbonImmutable} */
    public static function current(): array
    {
        $file = config('riverbank.build_file');
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return [
            'revision' => is_array($data) ? ($data['revision'] ?? null) : null,
            'updated_at' => is_array($data) && ! empty($data['updated_at'])
                ? CarbonImmutable::parse($data['updated_at'])->setTimezone(config('app.timezone'))
                : null,
        ];
    }
}
