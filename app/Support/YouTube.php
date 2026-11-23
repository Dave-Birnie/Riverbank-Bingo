<?php

namespace App\Support;

/**
 * Turns whatever the owner pastes (a watch link, a youtu.be link, a /live/
 * link) or a channel ID into an embeddable player URL.
 */
class YouTube
{
    public static function videoId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '~youtu\.be/([\w-]{11})~',
            '~youtube\.com/(?:live|embed|shorts)/([\w-]{11})~',
            '~youtube\.com/watch\?(?:.*&)?v=([\w-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    public static function embedUrl(?string $streamUrl, ?string $channelId): ?string
    {
        if ($id = self::videoId($streamUrl)) {
            return "https://www.youtube.com/embed/{$id}?autoplay=1&rel=0";
        }

        // Plays whatever the channel is broadcasting right now.
        if ($channelId && preg_match('/^UC[\w-]{22}$/', $channelId)) {
            return "https://www.youtube.com/embed/live_stream?channel={$channelId}&autoplay=1";
        }

        return null;
    }
}
