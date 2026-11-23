<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Announcement;
use App\Models\Game;
use App\Models\GameTemplate;
use App\Models\Page;
use App\Models\Post;
use App\Models\Winner;
use App\Support\Settings;
use App\Support\YouTube;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    private function biweeklyTuesday(): GameTemplate
    {
        return GameTemplate::create([
            'name' => 'Regular Tuesday',
            'title' => 'Tuesday Night Bingo',
            'weekday' => CarbonImmutable::TUESDAY,
            'interval_weeks' => 2,
            'start_time' => '18:45',
            'duration_minutes' => 150,
            'sales_close_minutes_before' => 45,
            'prizes' => [['name' => 'Jackpot', 'pattern' => 'Full card', 'amount' => 1000]],
        ]);
    }

    public function test_template_generates_games_every_second_tuesday(): void
    {
        $template = $this->biweeklyTuesday();

        // Starting from a Thursday snaps forward to the next Tuesday.
        $games = $template->generateGames(CarbonImmutable::parse('2026-11-05'), 3);

        $this->assertSame(
            ['2026-11-10 18:45', '2026-11-24 18:45', '2026-12-08 18:45'],
            $games->map(fn (Game $g) => $g->starts_at->format('Y-m-d H:i'))->all(),
        );
        $this->assertSame('21:15', $games->first()->ends_at->format('H:i'));
        $this->assertSame('18:00', $games->first()->card_sales_close_at->format('H:i'));
        $this->assertSame(1000, $games->first()->prizes[0]['amount']);
    }

    public function test_generating_twice_does_not_double_book(): void
    {
        $template = $this->biweeklyTuesday();
        $template->generateGames(CarbonImmutable::parse('2026-11-10'), 2);

        $again = $template->generateGames(CarbonImmutable::parse('2026-11-10'), 3);

        $this->assertCount(1, $again);
        $this->assertSame(3, Game::count());
    }

    public function test_next_run_of_games_continues_the_cadence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01 12:00', 'America/Toronto'));
        $template = $this->biweeklyTuesday();
        $template->generateGames(CarbonImmutable::parse('2026-11-10'), 2);

        $this->assertSame('2026-12-08', $template->suggestedFirstDate()->toDateString());
    }

    public function test_winner_cannot_be_published_without_consent(): void
    {
        $this->expectException(ValidationException::class);

        Winner::create(['display_name' => 'Mary K.', 'won_on' => '2026-11-10', 'is_published' => true]);
    }

    public function test_scheduled_posts_stay_hidden_until_their_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'America/Toronto'));
        Post::create(['title' => 'Holiday schedule', 'status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        Post::create(['title' => 'Welcome', 'status' => PostStatus::Published]);
        Post::create(['title' => 'Draft idea', 'status' => PostStatus::Draft]);

        $this->assertSame(['Welcome'], Post::query()->live()->pluck('title')->all());
        $this->assertSame('holiday-schedule', Post::firstWhere('title', 'Holiday schedule')->slug);

        $this->travel(1)->days();
        $this->assertCount(2, Post::query()->live()->get());
    }

    public function test_announcements_show_only_inside_their_window(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'America/Toronto'));
        Announcement::create(['message' => 'Always on']);
        Announcement::create(['message' => 'Tomorrow', 'starts_at' => now()->addDay()]);
        Announcement::create(['message' => 'Expired', 'ends_at' => now()->subMinute()]);
        Announcement::create(['message' => 'Switched off', 'is_active' => false]);

        $this->assertSame(['Always on'], Announcement::query()->showing()->pluck('message')->all());
    }

    public function test_page_copy_picks_up_contact_details_from_settings(): void
    {
        app(Settings::class)->setMany(['business_phone' => '450-632-4646', 'business_email' => 'info@example.com']);
        $page = Page::create(['key' => 'about', 'title' => 'About', 'body' => '<p>Call [phone] or write [email].</p>']);

        $this->assertSame(
            '<p>Call <a href="tel:4506324646">450-632-4646</a> or write <a href="mailto:info@example.com">info@example.com</a>.</p>',
            $page->renderedBody(),
        );
    }

    public function test_youtube_links_become_embed_urls(): void
    {
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0', YouTube::embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', null));
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0', YouTube::embedUrl('https://youtu.be/dQw4w9WgXcQ?si=abc', null));
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0', YouTube::embedUrl('https://www.youtube.com/live/dQw4w9WgXcQ', 'UCxxxxxxxxxxxxxxxxxxxxxx'));
        $this->assertSame('https://www.youtube.com/embed/live_stream?channel=UC1234567890abcdefghijkl&autoplay=1', YouTube::embedUrl(null, 'UC1234567890abcdefghijkl'));
        $this->assertNull(YouTube::embedUrl('not a link', 'not-a-channel'));
    }
}
