<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use App\Filament\Resources\GameTemplates\GameTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('templates')
                ->label('Add games from a template')
                ->color('gray')
                ->url(GameTemplateResource::getUrl()),
            CreateAction::make()->label('Add one game'),
        ];
    }
}
