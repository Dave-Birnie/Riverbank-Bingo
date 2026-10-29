<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Details the owner changes once and the whole site picks up: contact
 * details, the stream, progressive jackpots.
 *
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    /** Settings this page edits; anything else (like the live override) is left alone. */
    private const FIELDS = [
        'site_theme', 'business_phone', 'business_email', 'mailing_address', 'facebook_url',
        'youtube_channel_url', 'youtube_channel_id', 'stream_url', 'offline_message', 'offline_message_fr',
        'progressives', 'delivery_areas', 'delivery_areas_fr', 'assistant_enabled',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $this->form->fill(array_intersect_key($settings->all(), array_flip(self::FIELDS)));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Design')
                    ->description('How the public website looks. Switching takes effect immediately; nothing else changes.')
                    ->schema([
                        ToggleButtons::make('site_theme')
                            ->hiddenLabel()
                            ->options(Settings::THEMES)
                            ->icons(['black-gold' => Heroicon::OutlinedMoon, 'classic' => Heroicon::OutlinedSun])
                            ->inline()
                            ->required(),
                    ]),
                Section::make('Contact details')
                    ->description('Shown in the header, footer, Contact page and anywhere page copy says [phone] or [email].')
                    ->columns(2)
                    ->schema([
                        TextInput::make('business_phone')->label('Bingo hotline')->tel()->required(),
                        TextInput::make('business_email')->label('Email')->email()
                            ->helperText('Contact form messages are sent here.'),
                        Textarea::make('mailing_address')->rows(2),
                        TextInput::make('facebook_url')->label('Facebook page')->url(),
                    ]),
                Section::make('Live stream')
                    ->description('The Watch Live page plays whatever the channel is broadcasting. Paste a specific video link only to override that.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('youtube_channel_id')
                            ->label('YouTube channel ID')
                            ->placeholder('UC…')
                            ->regex('/^UC[\w-]{22}$/')
                            ->helperText('YouTube Studio → Settings → Channel → Advanced settings. Starts with "UC".'),
                        TextInput::make('youtube_channel_url')->label('YouTube channel link')->url(),
                        TextInput::make('stream_url')
                            ->label('Specific stream link (optional)')
                            ->url()
                            ->columnSpanFull(),
                        Textarea::make('offline_message')
                            ->label('Message when not live')
                            ->rows(2),
                        Textarea::make('offline_message_fr')
                            ->label('Message when not live (French)')
                            ->rows(2),
                    ]),
                Section::make('Progressive jackpots')
                    ->description('Shown on the homepage. Update the amounts after each game.')
                    ->schema([
                        Repeater::make('progressives')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('name')->required(),
                                TextInput::make('name_fr')->label('Name (French)'),
                                TextInput::make('amount')->numeric()->prefix('$')->minValue(0)->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderable()
                            ->addActionLabel('Add jackpot'),
                    ]),
                Section::make('Card delivery')
                    ->columns(2)
                    ->schema([
                        Textarea::make('delivery_areas')
                            ->label('Delivery areas')
                            ->rows(3)
                            ->helperText('One per line. Shown on the homepage and the Card Distributors page.'),
                        Textarea::make('delivery_areas_fr')
                            ->label('Delivery areas (French)')
                            ->rows(3)
                            ->helperText('Leave blank to use the English list.'),
                    ]),
                Section::make('Developer')
                    ->visible(fn () => auth()->user()?->isDeveloper())
                    ->schema([
                        Toggle::make('assistant_enabled')
                            ->label('AI assistant enabled')
                            ->helperText('Not built yet. Stays off until the assistant is purchased.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $data = $this->form->getState();

        if (! auth()->user()?->isDeveloper()) {
            unset($data['assistant_enabled']);
        }

        $settings->setMany(array_intersect_key($data, array_flip(self::FIELDS)));

        Notification::make()->title('Settings saved')->success()->send();
    }
}
