<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SiteSeeder::class);

        // Sample content and password-"password" logins: never on a server.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
