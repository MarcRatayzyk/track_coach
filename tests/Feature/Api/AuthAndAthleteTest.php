<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndAthleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_fetch_me(): void
    {
        $user = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@test.dev',
            'password' => Hash::make('password'),
            'role' => 'coach',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
