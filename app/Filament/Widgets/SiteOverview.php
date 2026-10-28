<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Games\GameResource;
use App\Models\Announcement;
use App\Models\ContactMessage;
use App\Support\LiveStatus;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $next = app(LiveStatus::class)->nextGame();
        $progressives = collect(app(Settings::class)->get('progressives', []));
        $showing = Announcement::query()->showing()->count();
        $unread = ContactMessage::query()->whereNull('read_at')->count();

        return [
            Stat::make('Next game', $next ? $next->starts_at->isoFormat('ddd, MMM D') : 'None scheduled')
                ->description($next ? $next->starts_at->format('g:i A').' · '.$next->starts_at->diffForHumans() : 'Add games from a template')
                ->url(GameResource::getUrl()),
            Stat::make('Progressive jackpots', '$'.number_format($progressives->sum(fn ($p) => (float) ($p['amount'] ?? 0))))
                ->description($progressives->map(fn ($p) => ($p['name'] ?? '').' $'.number_format((float) ($p['amount'] ?? 0)))->implode(' · ') ?: 'None set')
                ->url(SiteSettings::getUrl()),
            Stat::make('Announcements showing', $showing)
                ->description('In the scrolling banner')
                ->url(AnnouncementResource::getUrl()),
            Stat::make('Unread messages', $unread)
                ->description('From the contact form')
                ->color($unread ? 'warning' : 'gray')
                ->url(ContactMessageResource::getUrl()),
        ];
    }
}
