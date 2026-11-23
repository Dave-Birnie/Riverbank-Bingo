<?php

namespace App\Filament\Resources\Distributors;

use App\Enums\DistributorType;
use App\Filament\Resources\Distributors\Pages\ManageDistributors;
use App\Filament\Support\Bilingual;
use App\Models\Distributor;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class DistributorResource extends Resource
{
    protected static ?string $model = Distributor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Players';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('type')
                        ->options(DistributorType::class)
                        ->default(DistributorType::Pickup)
                        ->required(),
                    TextInput::make('phone')->tel()->maxLength(40),
                    TextInput::make('address')->maxLength(255),
                    TextInput::make('city')->maxLength(255),
                    TextInput::make('region')->label('Province / state')->maxLength(255)->default('QC'),
                    Bilingual::tabs(fn (string $lang, bool $english) => [
                        Textarea::make("hours{$lang}")->label('Hours')->rows(3)
                            ->placeholder($english ? "Mon–Sat 8 AM – 9 PM\nSun 10 AM – 6 PM" : "Lun–sam 8 h – 21 h\nDim 10 h – 18 h"),
                        Textarea::make("notes{$lang}")->label('Notes for players')->rows(3),
                    ]),
                ]),
            Section::make('Map pin')
                ->description('In Google Maps, right-click the spot and click the two numbers at the top of the menu to copy them. Paste the first into Latitude and the second into Longitude. Leave both empty to keep this distributor off the map.')
                ->columns(2)
                ->schema([
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90)->placeholder('45.4089'),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180)->placeholder('-73.6857'),
                ]),
            Toggle::make('is_active')->label('Show on the website')->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->weight('bold')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('city'),
                TextColumn::make('phone'),
                IconColumn::make('pinned')
                    ->label('On map')
                    ->state(fn (Distributor $record) => $record->hasPin())
                    ->boolean(),
                ToggleColumn::make('is_active')->label('Shown'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDistributors::route('/'),
        ];
    }
}
