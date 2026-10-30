<?php

namespace App\Filament\Resources\GameTemplates\Pages;

use App\Filament\Resources\GameTemplates\GameTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGameTemplates extends ManageRecords
{
    protected static string $resource = GameTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
