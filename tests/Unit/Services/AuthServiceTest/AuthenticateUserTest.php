<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class AuthenticateUserTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    #[Test]
    public function it_authenticates_a_user_and_returns_a_token()
    {
        // ---- Arrange ----
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'password' => 'hashed-password',
            'email_verified_at' => now(),
        ]);

        $credentials = [
            'User_Email' => 'test@example.com',
            'password' => 'password123',
        ];

        Auth::shouldReceive('guard->attempt')
            ->once()
            ->with($credentials)
            ->andReturn('fake-token');

        Auth::shouldReceive('guard->user')
            ->twice()
            ->andReturn($user);

        // ---- Act ----
        $result = $this->authService->authenticateUser($credentials);

        // ---- Assert ----
        /** @var \App\Helpers\ServiceResponse $result */
        $this->assertObjectHasProperty('data', $result);
        $this->assertObjectHasProperty('message', $result);
        $this->assertArrayHasKey('user', $result->data);
        $this->assertArrayHasKey('accessToken', $result->data);
        $this->assertEquals('fake-token', $result->data['accessToken']);
    }

    #[Test]
    public function it_fails_to_authenticate_with_invalid_credentials()
    {
        $credentials = [
            'User_Email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        Auth::shouldReceive('guard->attempt')
            ->once()
            ->with($credentials)
            ->andReturn(false);

        $result = $this->authService->authenticateUser($credentials);

        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid email or password', $result->error);
    }
}
