<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials drawn locally as an SVG. Filament's default fetches avatars from
 * ui-avatars.com, which would send the owner's name to a third party.
 */
class InitialsAvatar implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = collect(preg_split('/\s+/', trim(Filament::getNameForDefaultAvatar($record))))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#0E3648"/>'
            .'<text x="32" y="41" font-family="Arial,sans-serif" font-size="26" font-weight="700" fill="#F0C97F" text-anchor="middle">'
            .e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
