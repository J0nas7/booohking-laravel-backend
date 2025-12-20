<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user login success.
     */
    public function test_login_user_success()
    {
        $password = 'password123';
        $user = User::factory()->create(['password' => bcrypt($password)]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_user_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'The selected email is invalid.']);
    }

    /**
     * Test accessing authenticated user details.
     */
    public function test_authenticated_user_details()
    {
        $user = User::factory()->create();

        // Generate token for the user
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ]);
    }

    /**
     * Test accessing authenticated user details without authentication.
     */
    public function test_authenticated_user_details_unauthenticated()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated.']);
    }

    /**
     * Test logout.
     */
    public function test_logout_user_success()
    {
        $user = User::factory()->create();

        // Generate token for the user
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Edge Case: Logout without being logged in.
     */
    public function test_logout_user_not_logged_in()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
