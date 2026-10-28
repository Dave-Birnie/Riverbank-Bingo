<?php

namespace App\Filament\Resources\Distributors\Pages;

use App\Filament\Resources\Distributors\DistributorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDistributors extends ManageRecords
{
    protected static string $resource = DistributorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
