<?php

namespace Tests\Feature;

use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_complete_setup_via_signed_urls(): void
    {
        $athlete = User::query()->create([
            'name' => 'Jean Test',
            'email' => 'jean@example.com',
            'password' => Hash::make('unused-random'),
            'role' => 'athlete',
            'initial_setup_completed_at' => null,
        ]);

        AthleteProfile::query()->create(['user_id' => $athlete->id]);

        $showUrl = URL::temporarySignedRoute(
            'account.setup.show',
            now()->addDay(),
            ['user' => $athlete->id],
        );

        $this->get($showUrl)->assertOk();

        $postUrl = URL::temporarySignedRoute(
            'account.setup.update',
            now()->addDay(),
            ['user' => $athlete->id],
        );

        $this->post($postUrl, [
            'password' => 'motdepasse12',
            'password_confirmation' => 'motdepasse12',
            'weight_class' => '83 kg',
            'bio' => 'Objectif total 600',
        ])->assertRedirect(route('login'));

        $athlete->refresh();
        $this->assertNotNull($athlete->initial_setup_completed_at);
        $this->assertTrue(Hash::check('motdepasse12', $athlete->password));

        $profile = AthleteProfile::query()->where('user_id', $athlete->id)->first();
        $this->assertSame('83 kg', $profile->weight_class);
        $this->assertSame('Objectif total 600', $profile->bio);
    }
}
