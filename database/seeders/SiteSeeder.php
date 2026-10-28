<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The fixed set of pages the owner can edit. Safe to re-run in production:
 * it only adds pages that are missing and never overwrites edited copy.
 */
class SiteSeeder extends Seeder
{
    public const PAGES = [
        // The homepage's "title" is its big headline.
        'home' => 'Bingo night, at your table.',
        'about' => 'About Us',
        'how-to-play' => 'How to Play',
        'schedule' => 'Game Schedule',
        'watch-live' => 'Watch Live',
        'distributors' => 'Card Distributors',
        'winners' => 'Winners',
        'blog' => 'Blog',
        'contact' => 'Contact Us',
        'become-a-distributor' => 'Become a Distributor',
        'rules' => 'Rules',
        'privacy' => 'Privacy Policy',
    ];

    public function run(): void
    {
        foreach (self::PAGES as $key => $title) {
            Page::query()->firstOrCreate(['key' => $key], [
                'title' => $title,
                'intro' => $this->intro($key),
                'body' => $this->body($key),
            ]);
        }
    }

    private function intro(string $key): ?string
    {
        return match ($key) {
            'home' => 'Pick up your cards locally, tune in to our live broadcast from your own kitchen table, and call us the moment you have BINGO.',
            'about' => "Kahnawake's premier live-streamed bingo experience.",
            'how-to-play' => 'Three steps, then play from your own table.',
            'schedule' => 'Every game, every prize, all in one place.',
            'watch-live' => 'Get your cards ready. The game is broadcast live right here.',
            'distributors' => 'Pick up your cards from a trusted community distributor, or have them delivered.',
            'winners' => 'Real cards, real calls, real neighbours.',
            'blog' => 'News, specials and stories from Riverbank Bingo.',
            'contact' => "Questions about cards, games or payouts? We're happy to help.",
            'become-a-distributor' => 'Sell Riverbank Bingo cards in your community.',
            default => null,
        };
    }

    private function body(string $key): ?string
    {
        return match ($key) {
            'about' => file_get_contents(database_path('seeders/content/about.html')),
            'how-to-play' => <<<'HTML'
                <h2>1. Get your cards</h2>
                <p>Pick up physical cards from one of our community distributors, or ask about contactless delivery if you live further out. Card sales close before the game starts, so grab yours early.</p>
                <h2>2. Tune in to the live broadcast</h2>
                <p>Open the Watch Live page at game time. Every number is called on screen with colour-coded cues that match your cards.</p>
                <h2>3. Have BINGO? Call us</h2>
                <p>Call [phone] right away. We'll verify your card and confirm your win live on air.</p>
                HTML,
            'rules', 'privacy', 'become-a-distributor', 'contact' => '<p><em>Copy to come from the client.</em></p>',
            default => null,
        };
    }
}
