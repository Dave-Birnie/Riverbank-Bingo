<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn () => route('blog.show', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
