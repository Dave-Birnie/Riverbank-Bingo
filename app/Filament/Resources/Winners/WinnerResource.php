<?php

namespace App\Filament\Resources\Winners;

use App\Filament\Resources\Winners\Pages\ManageWinners;
use App\Models\Game;
use App\Models\Winner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WinnerResource extends Resource
{
    protected static ?string $model = Winner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Players';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('display_name')
                        ->label('Name as shown')
                        ->required()
                        ->helperText('First name and initial is usually enough, e.g. "Mary K."'),
                    DatePicker::make('won_on')->label('Date won')->default(now())->required(),
                    Select::make('game_id')
                        ->label('Game')
                        ->options(fn () => Game::query()->orderByDesc('starts_at')->limit(40)->get()
                            ->mapWithKeys(fn (Game $g) => [$g->id => $g->starts_at->isoFormat('MMM D, YYYY').' — '.$g->title]))
                        ->searchable(),
                    TextInput::make('prize_label')->label('Prize')->placeholder('Jackpot #3'),
                    TextInput::make('amount')->numeric()->prefix('$')->minValue(0),
                    FileUpload::make('photo_path')
                        ->label('Photo')
                        ->image()
                        ->disk('public')
                        ->directory('winners')
                        ->maxSize(4096),
                    Textarea::make('story')->rows(3)->columnSpanFull(),
                ]),
            Section::make('Publishing')
                ->schema([
                    Toggle::make('consent_confirmed')
                        ->label('The winner agreed to be featured on the website')
                        ->accepted(fn (Get $get) => (bool) $get('is_published'))
                        ->validationMessages(['accepted' => 'Confirm the winner agreed before publishing.']),
                    Toggle::make('is_published')->label('Show on the website')->live(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('won_on', 'desc')
            ->columns([
                ImageColumn::make('photo_path')->label('')->disk('public')->circular(),
                TextColumn::make('display_name')->label('Name')->searchable()->weight('bold'),
                TextColumn::make('won_on')->label('Date')->date('M j, Y')->sortable(),
                TextColumn::make('prize_label')->label('Prize'),
                TextColumn::make('amount')->money('CAD', locale: 'en_CA'),
                IconColumn::make('consent_confirmed')->label('Consent')->boolean(),
                IconColumn::make('is_published')->label('Shown')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWinners::route('/'),
        ];
    }
}
