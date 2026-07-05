<?php

namespace App\Console\Commands;

use App\Mail\CoachInvitationMail;
use App\Models\User;
use App\Support\AccountSetupUrlGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InviteCoachCommand extends Command
{
    protected $signature = 'coach:invite
                            {email : Adresse e-mail du coach}
                            {--name= : Nom affiché du coach}';

    protected $description = 'Invite un coach par e-mail avec un lien d’activation signé';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Un compte existe déjà avec l’e-mail {$email}.");

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        if ($name === '') {
            $name = Str::before($email, '@');
        }

        $coach = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(48),
            'role' => 'coach',
            'initial_setup_completed_at' => null,
        ]);

        $setupUrl = AccountSetupUrlGenerator::signedSetupUrl($coach);

        Mail::to($coach)->send(new CoachInvitationMail($coach, $setupUrl));

        $this->info("Invitation envoyée à {$email}.");
        $this->line("Lien d’activation (secours) : {$setupUrl}");

        return self::SUCCESS;
    }
}
