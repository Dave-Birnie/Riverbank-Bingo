<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Support\LiveStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveStatusTest extends TestCase
{
    use RefreshDatabase;

    private function at(string $localTime): void
    {
        $this->travelTo(CarbonImmutable::parse($localTime, 'America/Toronto'));
    }

    private function game(string $start, string $end, GameStatus $status = GameStatus::Scheduled): Game
    {
        return Game::create([
            'title' => 'Tuesday Night Bingo',
            'starts_at' => CarbonImmutable::parse($start, 'America/Toronto'),
            'ends_at' => CarbonImmutable::parse($end, 'America/Toronto'),
            'status' => $status,
        ]);
    }

    /** A fresh instance each time, as each web request would get. */
    private function live(): LiveStatus
    {
        $this->app->forgetScopedInstances();

        return app(LiveStatus::class);
    }

    public function test_not_live_without_games(): void
    {
        $this->at('2026-11-10 19:00');

        $this->assertFalse($this->live()->isLive());
        $this->assertSame('none', $this->live()->state()['source']);
    }

    public function test_live_inside_the_scheduled_window_only(): void
    {
        $game = $this->game('2026-11-10 18:45', '2026-11-10 21:15');

        $this->at('2026-11-10 18:44:59');
        $this->assertFalse($this->live()->isLive());

        $this->at('2026-11-10 18:45');
        $this->assertTrue($this->live()->isLive());
        $this->assertSame('schedule', $this->live()->state()['source']);
        $this->assertTrue($this->live()->state()['game']->is($game));

        $this->at('2026-11-10 21:15');
        $this->assertFalse($this->live()->isLive());
    }

    public function test_cancelled_games_never_go_live(): void
    {
        $this->game('2026-11-10 18:45', '2026-11-10 21:15', GameStatus::Cancelled);
        $next = $this->game('2026-11-24 18:45', '2026-11-24 21:15');

        $this->at('2026-11-10 19:00');

        $this->assertFalse($this->live()->isLive());
        $this->assertTrue($this->live()->state()['next_game']->is($next));
    }

    public function test_go_live_early_lasts_until_the_game_ends_plus_grace(): void
    {
        $game = $this->game('2026-11-10 18:45', '2026-11-10 21:15');
        $this->at('2026-11-10 18:30');

        $until = $this->live()->goLive();

        $this->assertTrue($until->eq(CarbonImmutable::parse('2026-11-10 21:45', 'America/Toronto')));
        $this->assertTrue($this->live()->isLive());
        $this->assertSame('manual', $this->live()->state()['source']);
        $this->assertTrue($this->live()->state()['game']->is($game));

        $this->at('2026-11-10 21:30');
        $this->assertTrue($this->live()->isLive(), 'still live during the grace period');

        $this->at('2026-11-10 21:45');
        $this->assertFalse($this->live()->isLive());
    }

    public function test_go_live_with_no_game_nearby_expires_on_its_own(): void
    {
        $this->game('2026-11-24 18:45', '2026-11-24 21:15');
        $this->at('2026-11-12 14:00');

        $this->live()->goLive();
        $this->assertTrue($this->live()->isLive());
        $this->assertNull($this->live()->state()['game'], 'a game two weeks out is not the one being streamed');

        $this->at('2026-11-12 17:00');
        $this->assertFalse($this->live()->isLive());
    }

    public function test_end_live_during_a_game_holds_off_until_the_window_closes(): void
    {
        $this->game('2026-11-10 18:45', '2026-11-10 21:15');
        $next = $this->game('2026-11-24 18:45', '2026-11-24 21:15');
        $this->at('2026-11-10 20:30');

        $this->live()->endLive();
        $this->assertFalse($this->live()->isLive());
        $this->assertSame('manual', $this->live()->state()['source']);

        $this->at('2026-11-10 21:10');
        $this->assertFalse($this->live()->isLive());

        // The override is gone, and the next game starts by itself.
        $this->at('2026-11-24 18:45');
        $this->assertNull($this->live()->override());
        $this->assertTrue($this->live()->isLive());
        $this->assertTrue($this->live()->state()['game']->is($next));
    }

    public function test_end_live_outside_a_window_just_clears_the_override(): void
    {
        $this->at('2026-11-12 14:00');
        $this->live()->goLive();

        $this->at('2026-11-12 15:00');
        $this->live()->endLive();

        $this->assertFalse($this->live()->isLive());
        $this->assertNull($this->live()->override());
    }

    public function test_go_live_after_an_overrun_keeps_the_site_live(): void
    {
        $this->game('2026-11-10 18:45', '2026-11-10 21:15');
        $this->at('2026-11-10 21:20');

        $this->live()->goLive();

        $this->at('2026-11-10 23:00');
        $this->assertTrue($this->live()->isLive());
    }

    public function test_times_hold_across_daylight_saving_changes(): void
    {
        // Clocks fall back on 2026-11-01 and spring forward on 2027-03-14.
        $this->game('2026-11-03 18:45', '2026-11-03 21:15');
        $this->game('2027-03-16 18:45', '2027-03-16 21:15');

        $this->travelTo(CarbonImmutable::parse('2026-11-03 23:45', 'UTC')); // 18:45 EST
        $this->assertTrue($this->live()->isLive());

        $this->travelTo(CarbonImmutable::parse('2027-03-16 22:45', 'UTC')); // 18:45 EDT
        $this->assertTrue($this->live()->isLive());

        $this->travelTo(CarbonImmutable::parse('2027-03-16 22:44', 'UTC'));
        $this->assertFalse($this->live()->isLive());
    }

    public function test_status_endpoint_reports_the_same_state(): void
    {
        $this->game('2026-11-10 18:45', '2026-11-10 21:15');
        $this->at('2026-11-10 19:00');

        $this->getJson('/api/status')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, private')
            ->assertJsonPath('live', true)
            ->assertJsonPath('source', 'schedule')
            ->assertJsonPath('game.title', 'Tuesday Night Bingo')
            ->assertJsonStructure(['live', 'source', 'game', 'next_game', 'stream' => ['embed_url', 'watch_url'], 'offline_message', 'generated_at']);
    }
}
