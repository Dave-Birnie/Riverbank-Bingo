<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View on site')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn () => PageResource::publicUrl($this->record))
                ->openUrlInNewTab(),
            Action::make('viewFrench')
                ->label('Voir (FR)')
                ->icon(Heroicon::OutlinedLanguage)
                ->color('gray')
                ->url(fn () => PageResource::publicUrl($this->record, 'fr'))
                ->openUrlInNewTab(),
        ];
    }
}
