<?php

namespace App\Filament\Resources\Winners\Pages;

use App\Filament\Resources\Winners\WinnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWinners extends ManageRecords
{
    protected static string $resource = WinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
