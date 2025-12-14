<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_successfully()
    {
        $data = [
            'acceptTerms' => true,
            'User_Name' => 'John Doe',
            'User_Email' => 'john@example.com',
            'User_Password' => 'password123',
            'User_Password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User was created.'
            ]);

        $this->assertDatabaseHas('Boo_Users', [
            'User_Email' => 'john@example.com'
        ]);
    }

    #[Test]
    public function cannot_register_with_existing_email()
    {
        $user = User::factory()->create(['User_Email' => 'john@example.com']);

        $data = [
            'User_Name' => 'John Doe',
            'User_Email' => 'john@example.com',
            'User_Password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(400)
            ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function user_can_login_successfully()
    {
        $user = User::factory()->create([
            'User_Password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'User_Email' => $user->User_Email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['accessToken', 'user']]);
    }

    #[Test]
    public function cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'User_Password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'User_Email' => $user->User_Email,
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    #[Test]
    public function unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_get_me()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'userData' => ['User_ID', 'User_Name', 'User_Email']
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_me()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function user_can_refresh_jwt_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/auth/refreshJWT');

        $response->assertStatus(200)
            ->assertJsonStructure(['accessToken', 'token_type', 'expires_in']);
    }

    #[Test]
    public function clone_token_generates_new_token_for_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/auth/clone-token');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'accessToken']]);
    }

    #[Test]
    public function clone_token_fails_without_token()
    {
        $response = $this->postJson('/api/auth/clone-token');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    #[Test]
    public function login_is_throttled_after_too_many_attempts()
    {
        $user = User::factory()->create([
            'User_Password' => bcrypt('password123'),
        ]);

        // 5 allowed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'User_Email' => $user->User_Email,
                'password' => 'wrongpassword',
            ])->assertStatus(401);
        }

        // 6th attempt should be throttled
        $response = $this->postJson('/api/auth/login', [
            'User_Email' => $user->User_Email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function password_reset_is_throttled()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password', [
                'User_Email' => 'nonexistent@example.com',
            ]);
        }

        $response = $this->postJson('/api/auth/forgot-password', [
            'User_Email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function jwt_endpoints_are_throttled_after_too_many_requests()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // 10 allowed requests
        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders([
                'Authorization' => "Bearer $token",
            ])->postJson('/api/auth/clone-token')
                ->assertStatus(200);
        }

        // 11th request should be throttled
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/auth/clone-token');

        $response->assertStatus(429);
    }
}
