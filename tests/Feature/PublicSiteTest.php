<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\PostStatus;
use App\Models\Announcement;
use App\Models\ContactMessage;
use App\Models\Game;
use App\Models\Post;
use App\Models\User;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Database\Seeders\SiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SiteSeeder::class);
        $this->at('2026-11-10 12:00');
    }

    /** Move the clock; live state is worked out once per request, so start afresh. */
    private function at(string $localTime): void
    {
        $this->travelTo(CarbonImmutable::parse($localTime, 'America/Toronto'));
        $this->app->forgetScopedInstances();
    }

    private function tuesdayGame(array $attributes = []): Game
    {
        return Game::create($attributes + [
            'title' => 'Tuesday Night Bingo',
            'starts_at' => CarbonImmutable::parse('2026-11-10 18:45', 'America/Toronto'),
            'ends_at' => CarbonImmutable::parse('2026-11-10 21:15', 'America/Toronto'),
            'prizes' => [['name' => 'Jackpot', 'pattern' => 'Full card', 'amount' => 1000]],
        ]);
    }

    public function test_every_public_page_renders(): void
    {
        $this->tuesdayGame();

        foreach (['/', '/schedule', '/watch-live', '/distributors', '/winners', '/news', '/contact',
            '/about', '/how-to-play', '/rules', '/privacy', '/become-a-distributor'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/not-a-page')->assertNotFound();
    }

    public function test_homepage_shows_the_flashboard_until_the_game_starts(): void
    {
        $this->tuesdayGame();

        $this->get('/')
            ->assertSee('data-live="0"', false)
            ->assertSee('data-flashboard', false)
            ->assertSee('Tuesday, November 10 at 6:45 PM ET');

        $this->at('2026-11-10 19:00');

        $this->get('/')
            ->assertSee('data-live="1"', false)
            ->assertSee('Our game is currently in session');
    }

    public function test_watch_live_embeds_the_stream_only_while_live(): void
    {
        $this->tuesdayGame();
        app(Settings::class)->set('youtube_channel_id', 'UC1234567890abcdefghijkl');

        $this->get('/watch-live')->assertSee('not live right now')->assertDontSee('<iframe', false);

        $this->at('2026-11-10 19:00');
        $this->get('/watch-live')->assertSee('youtube.com/embed/live_stream?channel=UC1234567890abcdefghijkl', false);
    }

    public function test_cancelled_games_show_on_the_schedule_and_in_the_banner(): void
    {
        $this->tuesdayGame(['status' => GameStatus::Cancelled, 'cancellation_note' => 'Weather']);
        Announcement::create(['message' => 'Holiday special on December 22']);

        $this->get('/schedule')->assertSee('Cancelled: Weather');
        $this->get('/')
            ->assertSee('Tuesday, November 10 game is cancelled: Weather.')
            ->assertSee('Holiday special on December 22');
    }

    public function test_phone_number_comes_from_settings_everywhere(): void
    {
        app(Settings::class)->set('business_phone', '450-555-0199');

        $this->get('/')->assertSee('tel:4505550199', false);
        $this->get('/about')->assertSee('<a href="tel:4505550199">450-555-0199</a>', false);
    }

    public function test_drafts_are_hidden_from_the_public_but_previewable_by_admins(): void
    {
        $post = Post::create(['title' => 'Draft news', 'status' => PostStatus::Draft]);

        $this->get('/news/draft-news')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/news/draft-news')->assertOk()->assertSee('Preview');
    }

    public function test_contact_form_saves_the_message(): void
    {
        $this->post('/contact', [
            'topic' => 'cards', 'name' => 'Jane', 'email' => 'jane@example.com', 'message' => 'Do you deliver to Châteauguay?',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas('contact_messages', ['email' => 'jane@example.com', 'topic' => 'cards']);
    }

    public function test_contact_form_drops_bot_submissions(): void
    {
        $this->post('/contact', [
            'topic' => 'general', 'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'spam', 'website' => 'http://spam.test',
        ])->assertRedirect('/contact');

        $this->assertSame(0, ContactMessage::count());
    }
}
