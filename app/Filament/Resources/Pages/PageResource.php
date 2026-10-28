<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The fixed set of site pages. The owner edits their copy but can't add or
 * remove pages, so there's no create or delete.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    /** Where each page lives on the public site. */
    public static function publicUrl(Page $page): string
    {
        return match ($page->key) {
            'home' => route('home'),
            'schedule' => route('schedule'),
            'watch-live' => route('watch'),
            'distributors' => route('distributors'),
            'winners' => route('winners'),
            'blog' => route('blog'),
            'contact' => route('contact'),
            default => route('page', $page->key),
        };
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->label(fn (?Page $record) => $record?->key === 'home' ? 'Headline' : 'Title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('intro')
                        ->label('Introduction')
                        ->rows(2)
                        ->helperText('The short line under the title.'),
                    RichEditor::make('body')
                        ->label('Page copy')
                        ->helperText('Type [phone] or [email] to insert the contact details from Settings.')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('pages')
                        ->fileAttachmentsVisibility('public'),
                    TextInput::make('meta_description')
                        ->label('Search engine description')
                        ->maxLength(255)
                        ->helperText('Optional. One sentence shown under the page in Google results.'),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->weight('bold'),
                TextColumn::make('address')
                    ->label('Web address')
                    ->state(fn (Page $record) => parse_url(static::publicUrl($record), PHP_URL_PATH) ?: '/')
                    ->color('gray'),
                TextColumn::make('updated_at')->label('Last edited')->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (Page $record) => static::publicUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
