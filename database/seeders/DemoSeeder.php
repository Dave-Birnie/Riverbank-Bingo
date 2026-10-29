<?php

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Enums\DistributorType;
use App\Enums\PostStatus;
use App\Models\Announcement;
use App\Models\Distributor;
use App\Models\GameTemplate;
use App\Models\Post;
use App\Models\User;
use App\Models\Winner;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Sample content for local development. Every name here is a placeholder, and
 * the logins use the password "password", so it only ever runs locally.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'owner@example.com'], [
            'name' => 'Riverbank Owner',
            'password' => 'password',
            'role' => 'owner',
        ]);

        User::query()->firstOrCreate(['email' => 'developer@example.com'], [
            'name' => 'Developer',
            'password' => 'password',
            'role' => 'developer',
        ]);

        app(Settings::class)->setMany([
            'business_phone' => '450-632-4646',
            'business_email' => 'info@example.com',
            'delivery_areas' => "Île-Perrot\nCoteau-du-Lac\nparts of New York",
            'delivery_areas_fr' => "L'Île-Perrot\nCoteau-du-Lac\ncertaines parties de l'État de New York",
            'progressives' => [
                ['name' => 'Progressive Jackpot', 'name_fr' => 'Gros lot progressif', 'amount' => 4250],
                ['name' => 'Bonanza', 'name_fr' => 'Bonanza', 'amount' => 1800],
            ],
        ]);

        // The real template comes from a migration; sample games are generated from it.
        $template = GameTemplate::query()->firstOrCreate(['name' => 'Every second Tuesday'], [
            'title' => 'Tuesday Night Bingo',
            'title_fr' => 'Bingo du mardi soir',
            'weekday' => CarbonImmutable::TUESDAY,
            'interval_weeks' => 2,
            'start_time' => '18:45',
            'duration_minutes' => 150,
            'sales_close_minutes_before' => 45,
        ]);

        if ($template->games()->doesntExist()) {
            $template->generateGames(CarbonImmutable::today(), 6);
        }

        foreach ([
            ['name' => 'Sample Depanneur', 'city' => 'Kahnawake', 'latitude' => 45.4089, 'longitude' => -73.6857],
            ['name' => 'Sample Gas Bar', 'city' => 'Kahnawake', 'latitude' => 45.4171, 'longitude' => -73.6950],
            ['name' => 'Sample Delivery Route', 'city' => 'Île-Perrot', 'type' => DistributorType::Delivery, 'latitude' => null, 'longitude' => null],
        ] as $i => $distributor) {
            Distributor::query()->firstOrCreate(['name' => $distributor['name']], $distributor + [
                'type' => DistributorType::Pickup,
                'region' => 'QC',
                'phone' => '450-000-0000',
                'hours' => "Mon–Sat 8 AM – 9 PM\nSun 10 AM – 6 PM",
                'hours_fr' => "Lun–sam 8 h – 21 h\nDim 10 h – 18 h",
                'sort_order' => $i,
            ]);
        }

        Winner::query()->firstOrCreate(['display_name' => 'Sample Winner'], [
            'won_on' => CarbonImmutable::today()->subWeeks(2),
            'prize_label' => 'Jackpot #3',
            'prize_label_fr' => 'Gros lot no 3',
            'amount' => 1000,
            'story' => 'Placeholder story. Replace with a real, consent-cleared winner.',
            'consent_confirmed' => true,
            'is_published' => true,
        ]);

        Announcement::query()->firstOrCreate(['message' => 'Progressive jackpot is now over $4,000. Get your cards early!'], [
            'message_fr' => 'Le gros lot progressif dépasse maintenant 4 000 $. Procurez-vous vos cartes tôt!',
            'type' => AnnouncementType::Banner,
        ]);

        Post::query()->firstOrCreate(['slug' => 'welcome-to-the-new-riverbank-bingo'], [
            'title' => 'Welcome to the new Riverbank Bingo',
            'title_fr' => 'Bienvenue sur le nouveau site de Riverbank Bingo',
            'excerpt' => 'Our new website is here, with the schedule, live stream and distributors all in one place.',
            'excerpt_fr' => 'Notre nouveau site est en ligne : l\'horaire, la diffusion en direct et les distributeurs, tout au même endroit.',
            'body' => '<p>Placeholder post for the staging review.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);
    }
}
