<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@trackcoach.dev';

    public const PASSWORD = 'AdminTrackCoach2026!';

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Admin Track Coach',
                'password' => self::PASSWORD,
                'role' => 'admin',
                'initial_setup_completed_at' => now(),
                'email_verified_at' => now(),
                'is_demo' => false,
                'demo_expires_at' => null,
                'trial_ends_at' => null,
                'disabled_at' => null,
            ],
        );
    }
}
