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

    public function test_auth_login_logout_and_profile_flow(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test1234'),
        ]);
        $user->assignRole('estudiante');

        $login = $this->postJson('/api/v1/login', [
            'email'    => $user->email,
            'password' => 'test1234',
        ]);

        $login->assertOk()->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer ' . $token)
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

public function test_login_fails_with_wrong_password(): void
{
    $user = User::factory()->create([
        'password' => bcrypt('correct123'),
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ])
    ->assertStatus(401);
}

public function test_login_fails_with_invalid_email(): void
{
    $this->postJson('/api/v1/login', [
        'email' => 'nonexistent@email.com',
        'password' => 'test1234',
    ])
    ->assertStatus(401);
}

public function test_logout_requires_authentication(): void
{
    $this->postJson('/api/v1/logout')
        ->assertUnauthorized();
}
