<?php

namespace App\Filament\Resources\Announcements;

use App\Enums\AnnouncementType;
use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'message';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('message')
                        ->label('Banner text')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull()
                        ->helperText('Scrolls across the top of every page. Cancelled games are announced automatically; no need to add those.'),
                    Select::make('type')->options(AnnouncementType::class)->default(AnnouncementType::Banner)->required(),
                    Toggle::make('is_active')->label('Switched on')->default(true)->inline(false),
                    TextInput::make('link_url')->label('Link (optional)')->url()->placeholder('https://'),
                    TextInput::make('link_label')->label('Link text')->placeholder('Learn more'),
                    DateTimePicker::make('starts_at')->label('Show from')->seconds(false)->helperText('Empty = right away'),
                    DateTimePicker::make('ends_at')->label('Show until')->seconds(false)->after('starts_at')->helperText('Empty = until switched off'),
                    Textarea::make('body')->label('Details (special events)')->rows(3)->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('message')->wrap()->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('showing')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Announcement $record) => match (true) {
                        ! $record->is_active => 'Off',
                        $record->isShowing() => 'Showing now',
                        $record->starts_at?->isFuture() => 'Scheduled',
                        default => 'Ended',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Showing now' => 'success',
                        'Scheduled' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')->label('From')->dateTime('M j, g:i A')->placeholder('—'),
                TextColumn::make('ends_at')->label('Until')->dateTime('M j, g:i A')->placeholder('—'),
                ToggleColumn::make('is_active')->label('On'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnnouncements::route('/'),
        ];
    }
}
