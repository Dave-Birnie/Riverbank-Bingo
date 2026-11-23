<?php

namespace App\Filament\Widgets;

use App\Support\LiveStatus;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/**
 * The dashboard's live switch: shows whether the site says we're live, and
 * the Go Live / End Live buttons.
 */
class LiveControl extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.live-control';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $live = app(LiveStatus::class);

        return [
            'state' => $live->state(),
        ];
    }

    public function goLiveAction(): Action
    {
        return Action::make('goLive')
            ->label('Go Live')
            ->icon(Heroicon::OutlinedSignal)
            ->color('danger')
            ->size('xl')
            ->requiresConfirmation()
            ->modalHeading('Go live now?')
            ->modalDescription('The homepage switches to "Our game is currently in session" and Watch Live shows the stream. It switches back on its own after the game\'s scheduled end, or you can press End Live.')
            ->modalSubmitActionLabel('Go Live')
            ->action(function (LiveStatus $live) {
                $until = $live->goLive();

                Notification::make()
                    ->title('You\'re live')
                    ->body('The site will switch back on its own at '.$until->format('g:i A').'.')
                    ->success()
                    ->send();
            });
    }

    public function endLiveAction(): Action
    {
        return Action::make('endLive')
            ->label('End Live')
            ->icon(Heroicon::OutlinedSignalSlash)
            ->color('gray')
            ->size('xl')
            ->requiresConfirmation()
            ->modalHeading('End the live game now?')
            ->modalDescription('The homepage goes back to the countdown for the next game.')
            ->modalSubmitActionLabel('End Live')
            ->action(function (LiveStatus $live) {
                $live->endLive();

                Notification::make()->title('Live mode ended')->success()->send();
            });
    }

    public function resumeAction(): Action
    {
        return Action::make('resume')
            ->label('Follow the schedule again')
            ->link()
            ->color('gray')
            ->action(fn (LiveStatus $live) => $live->resume());
    }
}
