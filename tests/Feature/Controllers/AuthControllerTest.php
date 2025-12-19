<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // ==== register() ====
    #[Test]
    public function user_can_register_successfully()
    {
        $data = [
            'acceptTerms' => true,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
    }

    #[Test]
    public function cannot_register_with_existing_email()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    // ==== login() ====
    #[Test]
    public function user_can_login_successfully()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function login_is_throttled_after_too_many_attempts()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // 5 allowed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ])->assertStatus(401);
        }

        // 6th attempt should be throttled
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    // ==== logout() ====
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

    // ==== me() ====
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
                'message',
                'data'
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_me()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    // ==== refreshJWT() ====
    #[Test]
    public function user_can_refresh_jwt_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/auth/refreshJWT');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['accessToken', 'token_type', 'expires_in']]);
    }

    // ==== cloneToken() ====
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

    // ==== forgotPassword() ====
    #[Test]
    public function password_reset_is_throttled()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password', [
                'email' => 'nonexistent@example.com',
            ]);
        }

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(429);
    }

    // ==== activate() ====
    #[Test]
    public function user_can_activate_account()
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
            'email_verification_token' => 'valid-token',
        ]);

        $response = $this->postJson('/api/auth/activate-account', [
            'token' => 'valid-token',
        ]);

        // We only assert structure/status because AuthService handles logic
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    // ==== resetPassword() ====
    #[Test]
    public function user_can_reset_password_with_valid_token()
    {
        // Arrange
        Notification::fake();
        $password = "newpassword123";
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Send reset link (this generates a token and "sends" email)
        Password::sendResetLink(['email' => $user->email]);

        // Assert notification was "sent"
        Notification::assertSentTo(
            [$user],
            ForgotPasswordNotification::class,
            function ($notification, $channels) use (&$token) {
                $token = $notification->getToken();
                return in_array('mail', $channels);
            }
        );

        $data = [
            'email' => $user->email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        // ==== Act ====
        $response = $this->postJson('/api/auth/reset-password', $data);

        // ==== Assert ====
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
