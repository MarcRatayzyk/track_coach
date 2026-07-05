<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccountSetupUrlGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSetupTest extends TestCase
{
    public function test_athlete_can_activate_account_via_signed_url(): void
    {
        $athlete = User::query()->create([
            'name' => 'Léa Martin',
            'email' => 'lea@example.com',
            'password' => 'temporary',
            'role' => 'athlete',
            'initial_setup_completed_at' => null,
        ]);

        $url = AccountSetupUrlGenerator::signedUpdateUrl($athlete);

        $this->post($url, [
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'weight_class' => '63 kg',
            'bio' => 'Objectif nationals',
        ])->assertRedirect(route('login'));

        $athlete->refresh();

        $this->assertNotNull($athlete->initial_setup_completed_at);
        $this->assertTrue(Hash::check('secret-password', $athlete->password));
        $this->assertSame('63 kg', $athlete->profile?->weight_class);
    }

    public function test_coach_can_activate_account_via_signed_url(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach Dupont',
            'email' => 'coach@example.com',
            'password' => 'temporary',
            'role' => 'coach',
            'initial_setup_completed_at' => null,
        ]);

        $url = AccountSetupUrlGenerator::signedUpdateUrl($coach);

        $this->post($url, [
            'password' => 'coach-secret',
            'password_confirmation' => 'coach-secret',
        ])->assertRedirect(route('login'));

        $coach->refresh();

        $this->assertNotNull($coach->initial_setup_completed_at);
        $this->assertTrue(Hash::check('coach-secret', $coach->password));
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $athlete = User::query()->create([
            'name' => 'Test Athlete',
            'email' => 'pending@example.com',
            'password' => 'temporary',
            'role' => 'athlete',
            'initial_setup_completed_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'account.setup.update',
            now()->subMinute(),
            ['user' => $athlete->id],
        );

        $this->post($url, [
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertForbidden();
    }
}
