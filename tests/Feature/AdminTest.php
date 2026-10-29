<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\Distributors\Pages\ManageDistributors;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Filament\Resources\GameTemplates\Pages\ManageGameTemplates;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Winners\Pages\ManageWinners;
use App\Filament\Widgets\LiveControl;
use App\Filament\Widgets\SiteOverview;
use App\Models\Game;
use App\Models\GameTemplate;
use App\Models\Page;
use App\Models\User;
use App\Support\LiveStatus;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Database\Seeders\SiteSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00', 'America/Toronto'));
        $this->seed([SiteSeeder::class, DemoSeeder::class]);
        $this->owner = User::query()->where('role', 'owner')->firstOrFail();
        $this->actingAs($this->owner);
    }

    public function test_every_admin_screen_loads(): void
    {
        foreach (['/admin', '/admin/games', '/admin/games/create', '/admin/game-templates', '/admin/distributors',
            '/admin/winners', '/admin/contact-messages', '/admin/announcements', '/admin/posts',
            '/admin/posts/create', '/admin/pages', '/admin/settings'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/admin/pages/'.Page::for('about')->id.'/edit')->assertOk();
    }

    public function test_livewire_screens_render(): void
    {
        foreach ([ListGames::class, CreateGame::class, ManageGameTemplates::class, ManageDistributors::class,
            ManageWinners::class, ManageContactMessages::class, ManageAnnouncements::class, ListPosts::class,
            CreatePost::class, ListPages::class, SiteSettings::class, LiveControl::class, SiteOverview::class] as $component) {
            Livewire::test($component)->assertOk();
        }
    }

    public function test_people_without_a_role_cannot_get_in(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'player']))->get('/admin')->assertForbidden();
        auth()->logout();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_pages_can_be_edited_in_both_languages_but_not_created(): void
    {
        $this->get('/admin/pages/create')->assertNotFound();

        $about = Page::for('about');
        Livewire::test(EditPage::class, ['record' => $about->getRouteKey()])
            ->fillForm(['title' => 'About Riverbank', 'title_fr' => 'À propos de Riverbank'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('About Riverbank', $about->fresh()->title);
        $this->assertSame('À propos de Riverbank', $about->fresh()->title_fr);
    }

    public function test_owner_can_switch_the_design(): void
    {
        Livewire::test(SiteSettings::class)
            ->assertSet('data.site_theme', 'black-gold')
            ->fillForm(['site_theme' => 'classic'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->app->forgetScopedInstances();
        $this->assertSame('classic', app(Settings::class)->theme());
    }

    public function test_owner_can_cancel_a_game_from_the_list(): void
    {
        $game = Game::query()->orderBy('starts_at')->first();

        Livewire::test(ListGames::class)
            ->callAction(TestAction::make('cancel')->table($game), data: ['note' => 'Storm warning'])
            ->assertHasNoFormErrors();

        $this->assertSame(GameStatus::Cancelled, $game->fresh()->status);
        $this->assertSame('Storm warning', $game->fresh()->cancellation_note);
    }

    public function test_owner_can_add_games_from_a_template(): void
    {
        $template = GameTemplate::first();
        $before = Game::count();

        Livewire::test(ManageGameTemplates::class)
            ->callAction(TestAction::make('generate')->table($template), data: ['first_date' => '2027-03-02', 'count' => 3])
            ->assertHasNoFormErrors();

        $this->assertSame($before + 3, Game::count());
        $this->assertTrue(Game::query()->where('starts_at', '2027-03-16 18:45:00')->exists());
    }

    public function test_go_live_and_end_live_from_the_dashboard(): void
    {
        Livewire::test(LiveControl::class)
            ->callAction('goLive')
            ->assertSee('The site is LIVE');

        $this->app->forgetScopedInstances();
        $this->assertTrue(app(LiveStatus::class)->isLive());

        Livewire::test(LiveControl::class)
            ->callAction('endLive')
            ->assertSee('Not live');

        $this->app->forgetScopedInstances();
        $this->assertFalse(app(LiveStatus::class)->isLive());
    }

    public function test_settings_save_and_leave_the_live_override_alone(): void
    {
        app(LiveStatus::class)->goLive();

        Livewire::test(SiteSettings::class)
            ->fillForm(['business_phone' => '450-555-0199', 'youtube_channel_id' => 'UC1234567890abcdefghijkl'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->app->forgetScopedInstances();
        $this->assertSame('450-555-0199', app(Settings::class)->get('business_phone'));
        $this->assertTrue(app(LiveStatus::class)->isLive());
    }

    public function test_only_developers_manage_logins(): void
    {
        $this->get('/admin/users')->assertForbidden();

        $developer = User::query()->where('role', 'developer')->firstOrFail();
        $this->flushSession();
        $this->actingAs($developer)->get('/admin/users')->assertOk();
    }

    public function test_deploy_creates_the_first_admin_login_once(): void
    {
        putenv('ADMIN_EMAIL=dev@riverbank.test');
        putenv('ADMIN_PASSWORD=a-long-enough-password');

        try {
            $this->artisan('riverbank:admin')->assertSuccessful();
            $this->artisan('riverbank:admin')->expectsOutputToContain('already exists')->assertSuccessful();
        } finally {
            putenv('ADMIN_EMAIL');
            putenv('ADMIN_PASSWORD');
        }

        $this->assertSame('developer', User::query()->where('email', 'dev@riverbank.test')->value('role'));
    }

    public function test_only_developers_can_switch_on_the_assistant(): void
    {
        Livewire::test(SiteSettings::class)
            ->fillForm(['assistant_enabled' => true])
            ->call('save');

        $this->app->forgetScopedInstances();
        $this->assertFalse(app(Settings::class)->get('assistant_enabled'));
    }
}
