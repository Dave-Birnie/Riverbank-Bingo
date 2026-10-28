<?php

namespace App\Filament\Resources\Games;

use App\Enums\GameStatus;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Models\Game;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Games';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Game')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->default('Tuesday Night Bingo'),
                    Select::make('status')
                        ->options(GameStatus::class)
                        ->default(GameStatus::Scheduled)
                        ->required()
                        ->live(),
                    DateTimePicker::make('starts_at')
                        ->label('Starts')
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('ends_at')
                        ->label('Ends')
                        ->seconds(false)
                        ->required()
                        ->after('starts_at')
                        ->helperText('The site shows "live" between the start and end times.'),
                    DateTimePicker::make('card_sales_close_at')
                        ->label('Card sales close')
                        ->seconds(false)
                        ->beforeOrEqual('starts_at'),
                    Textarea::make('cancellation_note')
                        ->label('Reason for cancelling (shown to players)')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('status') === GameStatus::Cancelled || $get('status') === GameStatus::Cancelled->value),
                ]),
            Section::make('Prizes')
                ->schema([
                    static::prizesRepeater(),
                    Textarea::make('notes')
                        ->label('Notes for players')
                        ->rows(2),
                ]),
        ])->columns(1);
    }

    /** Shared with game templates, so a template's lineup copies straight onto its games. */
    public static function prizesRepeater(): Repeater
    {
        return Repeater::make('prizes')
            ->hiddenLabel()
            ->schema([
                TextInput::make('name')->required()->placeholder('Jackpot #1'),
                TextInput::make('pattern')->placeholder('Full card'),
                TextInput::make('amount')->numeric()->prefix('$')->minValue(0),
            ])
            ->columns(3)
            ->defaultItems(0)
            ->reorderable()
            ->addActionLabel('Add prize');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Date')
                    ->dateTime('D, M j, Y · g:i A')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('card_sales_close_at')
                    ->label('Cards close')
                    ->time('g:i A')
                    ->placeholder('—'),
                TextColumn::make('prizes')
                    ->label('Prizes')
                    ->state(fn (Game $record) => collect($record->prizes)->sum(fn ($p) => (float) ($p['amount'] ?? 0)))
                    ->money('CAD', locale: 'en_CA'),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->where('ends_at', '>', now()))
                    ->default(),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel game')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Game $record) => ! $record->isCancelled())
                    ->modalDescription('Players will see the game marked as cancelled on the schedule, and a notice in the banner.')
                    ->schema([
                        Textarea::make('note')->label('Reason (optional, shown to players)')->rows(2),
                    ])
                    ->action(fn (Game $record, array $data) => $record->cancel($data['note'] ?? null)),
                Action::make('reinstate')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Game $record) => $record->isCancelled())
                    ->requiresConfirmation()
                    ->action(fn (Game $record) => $record->reinstate()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }
}
