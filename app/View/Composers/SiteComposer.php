<?php

namespace App\View\Composers;

use App\Enums\AnnouncementType;
use App\Enums\GameStatus;
use App\Models\Announcement;
use App\Models\Game;
use App\Support\Format;
use App\Support\LiveStatus;
use App\Support\Locales;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Everything the site-wide layout needs: contact details, the design the
 * owner picked, live state, the scrolling banner and the language links.
 */
class SiteComposer
{
    public function __construct(private Settings $settings, private LiveStatus $live) {}

    public function compose(View $view): void
    {
        $view->with([
            'settings' => $this->settings,
            'live' => $this->live->state(),
            'bannerItems' => $this->bannerItems(),
            'theme' => $this->settings->theme(),
            'alternates' => Locales::alternates(),
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
            ->map(function (Game $game) {
                $note = $game->localized('cancellation_note');

                return [
                    'text' => $note
                        ? __('The :date game is cancelled: :note', ['date' => Format::day($game->starts_at), 'note' => rtrim($note, '.')]).'.'
                        : __('The :date game is cancelled.', ['date' => Format::day($game->starts_at)]),
                    'url' => lroute('schedule'),
                    'label' => __('See schedule'),
                    'alert' => true,
                ];
            });

        $announcements = Announcement::query()->showing()
            ->where('type', AnnouncementType::Banner)
            ->get()
            ->map(fn (Announcement $a) => [
                'text' => $a->localized('message'),
                'url' => $a->link_url,
                'label' => $a->localized('link_label') ?: ($a->link_url ? __('Learn more') : null),
                'alert' => false,
            ]);

        return $cancellations->concat($announcements)->values();
    }
}
