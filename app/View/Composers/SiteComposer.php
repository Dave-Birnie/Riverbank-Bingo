<?php

namespace App\View\Composers;

use App\Enums\AnnouncementType;
use App\Enums\GameStatus;
use App\Models\Announcement;
use App\Models\Game;
use App\Support\LiveStatus;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Everything the site-wide layout needs: contact details, live state and the
 * scrolling banner.
 */
class SiteComposer
{
    public function __construct(private Settings $settings, private LiveStatus $live) {}

    public function compose(View $view): void
    {
        $state = $this->live->state();

        $view->with([
            'settings' => $this->settings,
            'live' => $state,
            'bannerItems' => $this->bannerItems(),
            'theme' => config('riverbank.theme') === 'black-gold' ? 'black-gold' : 'classic',
        ]);
    }

    /**
     * Cancellations come first and are generated from the schedule, so the
     * owner only has to cancel the game, not also write an announcement.
     *
     * @return Collection<int, array{text: string, url: ?string, label: ?string, alert: bool}>
     */
    private function bannerItems(): Collection
    {
        $cancellations = Game::query()
            ->where('status', GameStatus::Cancelled)
            ->whereBetween('starts_at', [now(), now()->addDays(21)])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Game $game) => [
                'text' => $game->starts_at->isoFormat('dddd, MMMM D').' game is cancelled'
                    .($game->cancellation_note ? ': '.rtrim($game->cancellation_note, '.').'.' : '.'),
                'url' => route('schedule'),
                'label' => 'See schedule',
                'alert' => true,
            ]);

        $announcements = Announcement::query()->showing()
            ->where('type', AnnouncementType::Banner)
            ->get()
            ->map(fn (Announcement $a) => [
                'text' => $a->message,
                'url' => $a->link_url,
                'label' => $a->link_label ?: ($a->link_url ? 'Learn more' : null),
                'alert' => false,
            ]);

        return $cancellations->concat($announcements)->values();
    }
}
