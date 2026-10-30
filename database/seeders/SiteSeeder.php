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
    /** English and French titles. The homepage's "title" is its big headline. */
    public const PAGES = [
        'home' => ['Bingo night, at your table.', 'Soirée bingo, à votre table.'],
        'about' => ['About Us', 'À propos de nous'],
        'how-to-play' => ['How to Play', 'Comment jouer'],
        'schedule' => ['Game Schedule', 'Horaire des parties'],
        'watch-live' => ['Watch Live', 'En direct'],
        'distributors' => ['Card Distributors', 'Distributeurs de cartes'],
        'winners' => ['Winners', 'Gagnants'],
        'blog' => ['News', 'Nouvelles'],
        'contact' => ['Contact Us', 'Nous joindre'],
        'become-a-distributor' => ['Become a Distributor', 'Devenir distributeur'],
        'rules' => ['Rules', 'Règlements'],
        'privacy' => ['Privacy Policy', 'Politique de confidentialité'],
    ];

    public function run(): void
    {
        foreach (self::PAGES as $key => [$title, $titleFr]) {
            Page::query()->firstOrCreate(['key' => $key], [
                'title' => $title,
                'title_fr' => $titleFr,
                'intro' => $this->intro($key),
                'intro_fr' => $this->introFr($key),
                'body' => $this->body($key),
                'body_fr' => $this->bodyFr($key),
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

    private function introFr(string $key): ?string
    {
        return match ($key) {
            'home' => 'Procurez-vous vos cartes près de chez vous, suivez notre diffusion en direct de votre table de cuisine et appelez-nous dès que vous avez BINGO.',
            'about' => 'La référence du bingo diffusé en direct à Kahnawake.',
            'how-to-play' => 'Trois étapes, puis jouez de chez vous.',
            'schedule' => 'Toutes les parties et tous les prix, au même endroit.',
            'watch-live' => 'Préparez vos cartes. La partie est diffusée en direct ici.',
            'distributors' => 'Procurez-vous vos cartes chez un distributeur de confiance de la communauté ou faites-les livrer.',
            'winners' => 'De vraies cartes, de vrais appels, de vrais voisins.',
            'blog' => 'Nouvelles, spéciaux et histoires de Riverbank Bingo.',
            'contact' => 'Des questions sur les cartes, les parties ou les paiements? Nous sommes là pour vous aider.',
            'become-a-distributor' => 'Vendez des cartes Riverbank Bingo dans votre communauté.',
            default => null,
        };
    }

    private function bodyFr(string $key): ?string
    {
        return match ($key) {
            'about' => file_get_contents(database_path('seeders/content/about.fr.html')),
            'how-to-play' => <<<'HTML'
                <h2>1. Procurez-vous vos cartes</h2>
                <p>Achetez vos cartes papier chez l'un de nos distributeurs de la communauté ou informez-vous sur la livraison sans contact si vous habitez plus loin. La vente des cartes se termine avant le début de la partie, alors procurez-vous les vôtres tôt.</p>
                <h2>2. Suivez la diffusion en direct</h2>
                <p>Ouvrez la page En direct à l'heure de la partie. Chaque numéro est annoncé à l'écran avec des repères de couleur qui correspondent à vos cartes.</p>
                <h2>3. Vous avez BINGO? Appelez-nous</h2>
                <p>Appelez le [phone] sans attendre. Nous vérifierons votre carte et confirmerons votre victoire en direct.</p>
                HTML,
            'rules', 'privacy', 'become-a-distributor', 'contact' => '<p><em>Texte à venir.</em></p>',
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
