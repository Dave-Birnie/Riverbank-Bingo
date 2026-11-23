<?php

namespace App\Filament\Support;

use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

/**
 * English and French tabs holding the same fields. The closure receives the
 * column suffix ('' or '_fr') and whether it's building the English tab, so
 * only English fields are required: a blank French field shows the English.
 */
class Bilingual
{
    public static function tabs(Closure $fields): Tabs
    {
        return Tabs::make('Language')
            ->tabs([
                Tab::make('English')->schema($fields('', true)),
                Tab::make('Français')->schema([
                    Text::make('Anything left blank here shows the English version on the French site.')->color('gray'),
                    ...$fields('_fr', false),
                ]),
            ])
            ->columnSpanFull();
    }
}
