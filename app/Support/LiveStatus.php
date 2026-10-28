<?php

namespace App\Support;

use App\Models\Game;
use Carbon\CarbonImmutable;

/**
 * The one place that decides whether a game is live. The homepage, schedule,
 * Watch Live and the status endpoint all read from here, so they can't
 * disagree.
 *
 * A manual override (Go Live / End Live on the dashboard) wins while it lasts;
 * otherwise we're live when now falls inside a scheduled game's start-to-end
 * window. Every override expires on its own, so a forgotten button can't leave
 * the site stuck live, and the next scheduled game still starts by itself.
 */
class LiveStatus
{
    /** How long a manual Go Live outlasts the scheduled end of its game. */
    public const GRACE_MINUTES = 30;

    /** Go Live with no game nearby lasts this long unless ended sooner. */
    public const STANDALONE_HOURS = 3;

    /** Going live this close to a game's start counts as starting that game early. */
    public const EARLY_START_MINUTES = 120;

    private ?array $state = null;

    public function __construct(private Settings $settings) {}

    /**
     * @return array{live: bool, source: 'manual'|'schedule'|'none', game: ?Game, next_game: ?Game, override_until: ?CarbonImmutable}
     */
    public function state(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $current = $this->currentGame();
        $override = $this->override();

        if ($override) {
            $live = $override['mode'] === 'live';
            $source = 'manual';
        } else {
            $live = $current !== null;
            $source = $live ? 'schedule' : 'none';
        }

        return $this->state = [
            'live' => $live,
            'source' => $source,
            'game' => $live ? ($current ?? $this->gameStartingSoon()) : null,
            'next_game' => $this->nextGame(),
            'override_until' => $override ? CarbonImmutable::parse($override['until']) : null,
        ];
    }

    public function isLive(): bool
    {
        return $this->state()['live'];
    }

    /** The non-cancelled game whose scheduled window contains now. */
    public function currentGame(): ?Game
    {
        $now = now();

        return Game::query()->scheduled()
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->orderBy('starts_at')
            ->first();
    }

    /** The next non-cancelled game that hasn't started yet. */
    public function nextGame(): ?Game
    {
        return Game::query()->scheduled()
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }

    /** The next game, if it starts soon enough that going live now means starting it early. */
    public function gameStartingSoon(): ?Game
    {
        $next = $this->nextGame();

        return $next && $next->starts_at->lte(now()->addMinutes(self::EARLY_START_MINUTES)) ? $next : null;
    }

    /** @return array{mode: 'live'|'off', until: string}|null the override, if one is still in force */
    public function override(): ?array
    {
        $override = $this->settings->get('live_override');

        if (! is_array($override) || empty($override['until']) || ! in_array($override['mode'] ?? null, ['live', 'off'], true)) {
            return null;
        }

        return CarbonImmutable::parse($override['until'])->isFuture() ? $override : null;
    }

    /**
     * Go live now. Lasts until the current game's scheduled end (or the next
     * game's, if it starts soon) plus a grace period, or a few hours if there's
     * no game nearby.
     */
    public function goLive(): CarbonImmutable
    {
        $game = $this->currentGame() ?? $this->gameStartingSoon();

        $until = $game
            ? CarbonImmutable::parse($game->ends_at)->addMinutes(self::GRACE_MINUTES)
            : CarbonImmutable::now()->addHours(self::STANDALONE_HOURS);

        $this->settings->set('live_override', ['mode' => 'live', 'until' => $until->toIso8601String()]);
        $this->state = null;

        return $until;
    }

    /**
     * Stop being live now. During a scheduled window this holds the site off
     * until that window closes; otherwise the schedule already says we're off,
     * so the override is simply cleared.
     */
    public function endLive(): void
    {
        $current = $this->currentGame();

        $this->settings->set('live_override', $current
            ? ['mode' => 'off', 'until' => CarbonImmutable::parse($current->ends_at)->toIso8601String()]
            : null);
        $this->state = null;
    }

    /** Drop any override and follow the schedule. */
    public function resume(): void
    {
        $this->settings->set('live_override', null);
        $this->state = null;
    }

    /** The public status payload, shared by the JSON endpoint and the pages. */
    public function toArray(): array
    {
        $state = $this->state();

        return [
            'live' => $state['live'],
            'source' => $state['source'],
            'game' => $this->gameSummary($state['game']),
            'next_game' => $this->gameSummary($state['next_game']),
            'stream' => [
                'embed_url' => YouTube::embedUrl($this->settings->get('stream_url'), $this->settings->get('youtube_channel_id')),
                'watch_url' => route('watch'),
            ],
            'offline_message' => $this->settings->get('offline_message'),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function gameSummary(?Game $game): ?array
    {
        return $game ? [
            'id' => $game->id,
            'title' => $game->title,
            'starts_at' => $game->starts_at->toIso8601String(),
            'ends_at' => $game->ends_at->toIso8601String(),
        ] : null;
    }
}
