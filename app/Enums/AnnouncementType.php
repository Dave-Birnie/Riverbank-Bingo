<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnnouncementType: string implements HasLabel
{
    case Banner = 'banner';
    case Event = 'event';

    public function getLabel(): string
    {
        return match ($this) {
            self::Banner => 'Scrolling banner',
            self::Event => 'Special event',
        };
    }
}
