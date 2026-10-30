<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The client's real schedule as a starting template: every second Tuesday at
 * 6:45 PM with three $1,000 jackpots. The owner picks the first date when
 * adding games, and can change anything here. Runs once, and only on an empty
 * table, so it never brings back a template the owner deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('game_templates')->exists()) {
            return;
        }

        $jackpot = fn (int $n) => ['name' => "Jackpot #{$n}", 'name_fr' => "Gros lot no {$n}", 'pattern' => null, 'pattern_fr' => null, 'amount' => 1000];

        DB::table('game_templates')->insert([
            'name' => 'Every second Tuesday',
            'title' => 'Tuesday Night Bingo',
            'title_fr' => 'Bingo du mardi soir',
            'weekday' => 2,
            'interval_weeks' => 2,
            'start_time' => '18:45',
            'duration_minutes' => 150,
            'sales_close_minutes_before' => 45,
            'prizes' => json_encode([$jackpot(1), $jackpot(2), $jackpot(3)]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('game_templates')->where('name', 'Every second Tuesday')->delete();
    }
};
