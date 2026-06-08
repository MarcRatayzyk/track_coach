<?php

namespace Tests\Feature;

use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoachAthleteRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_athlete_and_attach_to_roster(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        $response = $this->actingAs($coach)
            ->post('/coach/athletes', [
                'first_name' => 'Marie',
                'last_name' => 'Curie',
                'email' => 'nouveau@example.com',
                'feedback_frequency' => 'weekly',
            ]);

        $response->assertRedirect(route('athletes.index'));
        $response->assertSessionHas('first_login_url');
        $this->assertStringContainsString(
            '/account/setup/',
            (string) $response->session()->get('first_login_url'),
        );

        $athlete = User::query()->where('email', 'nouveau@example.com')->first();
        $this->assertNotNull($athlete);
        $this->assertSame('athlete', $athlete->role);
        $this->assertSame('Marie Curie', $athlete->name);
        $this->assertNull($athlete->initial_setup_completed_at);
        $this->assertTrue($coach->athletes()->where('athlete_id', $athlete->id)->exists());
        $this->assertNotNull(AthleteProfile::query()->where('user_id', $athlete->id)->first());
    }

    public function test_coach_can_detach_athlete_from_roster(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);
        $athlete = User::query()->create([
            'name' => 'Athlète',
            'email' => 'athlete@example.com',
            'password' => Hash::make('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);
        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        $this->actingAs($coach)
            ->delete('/coach/athletes/'.$athlete->id)
            ->assertRedirect(route('athletes.index'));

        $this->assertFalse($coach->athletes()->where('athlete_id', $athlete->id)->exists());
        $this->assertTrue(User::query()->whereKey($athlete->id)->exists());
    }

    public function test_non_coach_cannot_create_athlete_via_web(): void
    {
        $athlete = User::query()->create([
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => Hash::make('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->post('/coach/athletes', [
                'first_name' => 'X',
                'last_name' => 'Y',
                'email' => 'x@example.com',
            ])
            ->assertForbidden();
    }
}
