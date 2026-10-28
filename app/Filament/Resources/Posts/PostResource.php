<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'news post';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Section::make()
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Web address')
                            ->prefix('/news/')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Textarea::make('excerpt')
                            ->label('Summary')
                            ->rows(2)
                            ->helperText('Shown on the news list and in search results.'),
                        RichEditor::make('body')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('posts')
                            ->fileAttachmentsVisibility('public'),
                    ]),
                Section::make('Publishing')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->options(PostStatus::class)
                            ->default(PostStatus::Draft)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Publish date')
                            ->seconds(false)
                            ->helperText('Pick a future date to schedule the post. Leave empty to publish right away.'),
                        FileUpload::make('cover_image_path')
                            ->label('Cover image')
                            ->image()
                            ->disk('public')
                            ->directory('posts')
                            ->maxSize(4096),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold')->wrap(),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Post $record) => match (true) {
                        $record->status === PostStatus::Draft => 'Draft',
                        $record->isScheduled() => 'Scheduled',
                        default => 'Published',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Published' => 'success',
                        'Scheduled' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('published_at')->label('Date')->dateTime('M j, Y g:i A')->sortable()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (Post $record) => route('blog.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
