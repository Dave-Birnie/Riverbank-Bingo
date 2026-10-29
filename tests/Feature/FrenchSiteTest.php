<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\PostStatus;
use App\Models\Announcement;
use App\Models\ContactMessage;
use App\Models\Game;
use App\Models\Post;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Database\Seeders\SiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrenchSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SiteSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'America/Toronto'));
        $this->app->forgetScopedInstances();
    }

    private function tuesdayGame(array $attributes = []): Game
    {
        return Game::create($attributes + [
            'title' => 'Tuesday Night Bingo',
            'title_fr' => 'Bingo du mardi soir',
            'starts_at' => CarbonImmutable::parse('2026-11-10 18:45', 'America/Toronto'),
            'ends_at' => CarbonImmutable::parse('2026-11-10 21:15', 'America/Toronto'),
            'card_sales_close_at' => CarbonImmutable::parse('2026-11-10 18:00', 'America/Toronto'),
            'prizes' => [['name' => 'Jackpot #1', 'name_fr' => 'Gros lot no 1', 'pattern' => 'Full card', 'amount' => 1000]],
        ]);
    }

    public function test_every_french_page_renders_in_french(): void
    {
        $this->tuesdayGame();

        foreach (['/fr', '/fr/horaire', '/fr/en-direct', '/fr/distributeurs', '/fr/gagnants', '/fr/nouvelles',
            '/fr/contact', '/fr/a-propos', '/fr/comment-jouer', '/fr/reglements', '/fr/confidentialite',
            '/fr/devenir-distributeur'] as $url) {
            $this->get($url)->assertOk()->assertSee('lang="fr-CA"', false)->assertSee('Horaire');
        }
    }

    public function test_french_home_uses_french_copy_dates_and_money(): void
    {
        $this->tuesdayGame();
        app(Settings::class)->set('progressives', [['name' => 'Progressive Jackpot', 'name_fr' => 'Gros lot progressif', 'amount' => 4250]]);

        $this->get('/fr')
            ->assertSee('Soirée bingo, à votre table.')
            ->assertSee('Le mardi 10 novembre à 18 h 45 HE, en direct sur YouTube')
            ->assertSee('Gros lot progressif')
            ->assertSee("4\u{00A0}250\u{00A0}$")
            ->assertSee('Bingo du mardi soir')
            ->assertDontSee('Tuesday Night Bingo');
    }

    public function test_french_schedule_shows_french_prizes_and_falls_back_to_english(): void
    {
        $this->tuesdayGame();

        $this->get('/fr/horaire')
            ->assertSee('Gros lot no 1')
            ->assertSee("1\u{00A0}000\u{00A0}$")
            ->assertSee('fin des ventes à 18 h')
            ->assertSee('Full card'); // no French pattern given, so the English shows
    }

    public function test_untranslated_content_shows_the_english(): void
    {
        Announcement::create(['message' => 'Holiday special on December 22']);
        $this->tuesdayGame(['status' => GameStatus::Cancelled, 'cancellation_note' => 'Weather', 'cancellation_note_fr' => 'Météo']);

        $this->get('/fr')
            ->assertSee('Holiday special on December 22')
            ->assertSee('La partie du mardi 10 novembre est annulée : Météo.');
    }

    public function test_language_switch_links_to_the_same_page(): void
    {
        Post::create(['title' => 'Welcome', 'title_fr' => 'Bienvenue', 'slug' => 'welcome', 'status' => PostStatus::Published]);

        $this->get('/schedule')
            ->assertSee('href="'.url('/fr/horaire').'"', false)
            ->assertSee('hreflang="fr-CA" href="'.url('/fr/horaire').'"', false);

        $this->get('/fr/a-propos')->assertSee('href="'.url('/about').'"', false);

        $this->get('/fr/nouvelles/welcome')
            ->assertSee('Bienvenue')
            ->assertSee('href="'.url('/news/welcome').'"', false);
    }

    public function test_contact_form_answers_in_french(): void
    {
        $this->from('/fr/contact')
            ->post('/fr/contact', ['topic' => 'general', 'name' => 'Marie', 'message' => 'Bonjour'])
            ->assertRedirect('/fr/contact')
            ->assertSessionHasErrors(['email' => 'Le champ courriel est obligatoire.']);

        $this->post('/fr/contact', ['topic' => 'cards', 'name' => 'Marie', 'email' => 'marie@example.com', 'message' => 'Bonjour'])
            ->assertRedirect('/fr/contact');

        $this->assertSame('fr', ContactMessage::first()->locale);
    }

    public function test_the_design_follows_the_admin_setting(): void
    {
        $this->get('/')->assertSee('data-theme="black-gold"', false);

        app(Settings::class)->set('site_theme', 'classic');
        $this->app->forgetScopedInstances();

        $this->get('/')->assertSee('data-theme="classic"', false);
    }
}
