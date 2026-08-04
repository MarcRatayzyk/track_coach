<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Command;

class EnsureAdminUserCommand extends Command
{
    protected $signature = 'admin:ensure
                            {--email= : Email admin (défaut admin@trackcoach.dev)}
                            {--password= : Mot de passe (défaut AdminTrackCoach2026!)}
                            {--name= : Nom affiché}';

    protected $description = 'Crée ou met à jour le compte administrateur';

    public function handle(): int
    {
        $email = strtolower(trim((string) ($this->option('email') ?: AdminUserSeeder::EMAIL)));
        $password = (string) ($this->option('password') ?: AdminUserSeeder::PASSWORD);
        $name = trim((string) ($this->option('name') ?: 'Admin Track Coach'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'Admin Track Coach',
                'password' => $password,
                'role' => 'admin',
                'initial_setup_completed_at' => now(),
                'email_verified_at' => now(),
                'is_demo' => false,
                'demo_expires_at' => null,
                'trial_ends_at' => null,
                'disabled_at' => null,
            ],
        );

        $this->info("Compte admin prêt : {$user->email}");
        $this->line('Mot de passe : '.$password);

        return self::SUCCESS;
    }
}
