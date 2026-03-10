<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test1234'),
        ]);

        $user->assignRole('estudiante');

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test1234',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user'
        ]);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $login = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test1234'),
        ]);

        $user->assignRole('estudiante');

        $login = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test1234',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }
}
