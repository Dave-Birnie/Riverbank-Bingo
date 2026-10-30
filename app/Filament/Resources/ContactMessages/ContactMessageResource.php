<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Players';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'message';

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactMessage::query()->whereNull('read_at')->count();

        return $unread ? (string) $unread : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('topic')->state(fn (ContactMessage $record) => $record->topicLabel()),
            TextEntry::make('created_at')->label('Received')->dateTime('M j, Y g:i A'),
            TextEntry::make('name'),
            TextEntry::make('email')->url(fn (ContactMessage $record) => 'mailto:'.$record->email),
            TextEntry::make('phone')->placeholder('—'),
            TextEntry::make('message')->columnSpanFull()->extraAttributes(['style' => 'white-space: pre-line']),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable()
                    ->weight(fn (ContactMessage $record) => $record->read_at ? null : 'bold'),
                TextColumn::make('topic')->badge()->formatStateUsing(fn (string $state) => ContactMessage::TOPICS[$state] ?? $state),
                TextColumn::make('message')->limit(70)->color('gray'),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Read')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->mountUsing(fn (ContactMessage $record) => $record->read_at ?: $record->update(['read_at' => now()])),
                Action::make('reply')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->url(fn (ContactMessage $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: your message to Riverbank Bingo')),
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
            'index' => ManageContactMessages::route('/'),
        ];
    }
}
