<?php

namespace App\Filament\Resources\GameTemplates;

use App\Filament\Resources\Games\GameResource;
use App\Filament\Resources\GameTemplates\Pages\ManageGameTemplates;
use App\Models\GameTemplate;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GameTemplateResource extends Resource
{
    protected static ?string $model = GameTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Games';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'game template';

    public const WEEKDAYS = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    public const INTERVALS = [1 => 'Every week', 2 => 'Every second week', 3 => 'Every third week', 4 => 'Every fourth week'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Template name')
                        ->required()
                        ->placeholder('Regular Tuesday'),
                    TextInput::make('title')
                        ->label('Game title players see')
                        ->required()
                        ->default('Tuesday Night Bingo'),
                    Select::make('weekday')
                        ->options(self::WEEKDAYS)
                        ->default(2)
                        ->required(),
                    Select::make('interval_weeks')
                        ->label('How often')
                        ->options(self::INTERVALS)
                        ->default(2)
                        ->required(),
                    TimePicker::make('start_time')
                        ->label('Start time')
                        ->seconds(false)
                        ->format('H:i')
                        ->default('18:45')
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->label('Length (minutes)')
                        ->numeric()
                        ->minValue(15)
                        ->default(150)
                        ->required(),
                    TextInput::make('sales_close_minutes_before')
                        ->label('Card sales close (minutes before start)')
                        ->numeric()
                        ->minValue(0)
                        ->default(45),
                ]),
            Section::make('Prizes')
                ->schema([
                    GameResource::prizesRepeater(),
                    Textarea::make('notes')->label('Notes for players')->rows(2),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold'),
                TextColumn::make('schedule')
                    ->state(fn (GameTemplate $record) => self::INTERVALS[$record->interval_weeks] ?? '')
                    ->description(fn (GameTemplate $record) => self::WEEKDAYS[$record->weekday].' at '
                        .CarbonImmutable::createFromFormat('H:i', substr($record->start_time, 0, 5))->format('g:i A')),
                TextColumn::make('games_count')
                    ->label('Games made')
                    ->counts('games'),
            ])
            ->recordActions([
                Action::make('generate')
                    ->label('Add games')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('primary')
                    ->modalHeading(fn (GameTemplate $record) => "Add games from \"{$record->name}\"")
                    ->modalDescription('Dates that already have a game are skipped, so you can run this again safely.')
                    ->fillForm(fn (GameTemplate $record) => [
                        'first_date' => $record->suggestedFirstDate()->toDateString(),
                        'count' => 6,
                    ])
                    ->schema([
                        DatePicker::make('first_date')
                            ->label('First game date')
                            ->required()
                            ->helperText('Moved forward to the template\'s weekday if needed.'),
                        TextInput::make('count')
                            ->label('How many games')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(26)
                            ->required(),
                    ])
                    ->action(function (GameTemplate $record, array $data) {
                        $created = $record->generateGames(CarbonImmutable::parse($data['first_date']), (int) $data['count']);

                        Notification::make()
                            ->title($created->isEmpty() ? 'No new games — those dates are already booked' : 'Added '.$created->count().' '.str('game')->plural($created->count()))
                            ->body($created->isEmpty() ? null : 'From '.$created->first()->starts_at->isoFormat('MMM D').' to '.$created->last()->starts_at->isoFormat('MMM D, YYYY').'.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGameTemplates::route('/'),
        ];
    }
}
