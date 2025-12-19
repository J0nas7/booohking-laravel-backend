<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class CloneTokenTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    #[Test]
    public function it_clones_token_successfully_for_authenticated_user()
    {
        // ---- Arrange ----
        // Create a user in the database
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act as the user (authenticate)
        $this->actingAs($user, 'api');

        // Mock JWTAuth to generate a token for the authenticated user
        $mockToken = JWTAuth::fromUser($user); // This generates the actual token

        // ---- Act ----
        $result = $this->authService->cloneToken();

        // ---- Assert ----
        // Assert the structure of the response
        $this->assertObjectHasProperty('data', $result);
        $this->assertObjectHasProperty('message', $result);
        $this->assertArrayHasKey('user', $result->data);
        $this->assertArrayHasKey('accessToken', $result->data);
        $this->assertEquals('New token generated successfully', $result->message);

        // Assert that the token returned is a valid JWT (has 3 parts separated by '.')
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $result->data['accessToken']);
    }

    #[Test]
    public function it_fails_to_clone_token_when_no_user_is_authenticated()
    {
        // ---- Arrange ----
        // Simulate no authenticated user
        Auth::shouldReceive('guard')->once()->with('api')->andReturnSelf();
        Auth::shouldReceive('user')->once()->andReturnNull(); // No user authenticated

        // ---- Act ----
        $result = $this->authService->cloneToken();

        // ---- Assert ----
        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid or expired token', $result->error);
    }
}
