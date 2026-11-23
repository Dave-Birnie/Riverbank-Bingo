<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Creates the first developer login during a deploy, from the ADMIN_EMAIL and
 * ADMIN_PASSWORD environment variables. Never touches an existing login, so it
 * is safe to run on every deploy.
 */
class CreateAdmin extends Command
{
    protected $signature = 'riverbank:admin';

    protected $description = 'Create the developer login from ADMIN_EMAIL / ADMIN_PASSWORD if it does not exist yet';

    public function handle(): int
    {
        $email = (string) getenv('ADMIN_EMAIL');
        $password = (string) getenv('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            $this->line('ADMIN_EMAIL / ADMIN_PASSWORD not set; no login created.');

            return self::SUCCESS;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->line("Login {$email} already exists; left as it is.");

            return self::SUCCESS;
        }

        if (strlen($password) < 12) {
            $this->error('ADMIN_PASSWORD must be at least 12 characters.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => getenv('ADMIN_NAME') ?: 'Developer',
            'email' => $email,
            'password' => $password,
            'role' => 'developer',
        ]);

        $this->info("Created developer login {$email}.");

        return self::SUCCESS;
    }
}
