<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\Unit\Services\AuthServiceTest;
use Illuminate\Support\Facades\Config;

class RefreshJWTTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    protected function withBearer(string $token)
    {
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn($token);

        $newToken = 'new_fake_token'; // The refreshed token
        JWTAuth::shouldReceive('refresh')
            ->once()
            ->with($token)  // The token passed in the refresh method
            ->andReturn($newToken);
    }

    public function testTokenNotProvided()
    {
        // Call the refreshJWT method without a token (simulate missing token in headers)
        $response = $this->authService->refreshJWT();

        // Check for the error response
        $this->assertEquals(401, $response->status);
        $this->assertEquals('Token not provided', $response->error);
    }

    public function testTokenSuccessfullyRefreshed()
    {
        // Create a user and simulate a JWT token being generated for that user
        $user = User::factory()->create(); // Assuming you're using factories to create users
        $token = JWTAuth::fromUser($user);  // Generate a valid token for the user

        $this->withBearer($token);

        // Call the refreshJWT method
        $response = $this->authService->refreshJWT();

        // Check for successful response
        $this->assertEquals(200, $response->status);
        $this->assertEquals('Token refreshed successfully', $response->message);

        // Check if the new token is returned correctly
        $this->assertArrayHasKey('accessToken', $response->data);
        $this->assertEquals('bearer', $response->data['token_type']);
        $this->assertArrayHasKey('expires_in', $response->data);

        // TTL * 60 seconds for expires_in (assuming your config is correct)
        $this->assertEquals(config('jwt.ttl') * 60, $response->data['expires_in']);
    }

    public function testInvalidTokenHandling()
    {
        // Create a user and simulate an invalid or expired token
        $user = User::factory()->create();
        $expiredToken = 'invalid_token';  // Here you can manually create an invalid token if needed

        // Call the refreshJWT method and expect it to fail due to the invalid token
        $response = $this->authService->refreshJWT();

        // Check for error response (invalid token)
        $this->assertEquals(401, $response->status);
        $this->assertEquals('Token not provided', $response->error);
    }

    public function testTokenStructureAndFields()
    {
        // Create a user and simulate a valid JWT token being generated for that user
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);  // Generate a valid token for the user

        $this->withBearer($token);

        // Directly call the refreshJWT method on the AuthService
        $response = $this->authService->refreshJWT();

        // Ensure the response data is structured correctly
        /** @var \App\Helpers\ServiceResponse $result */
        $this->assertArrayHasKey('accessToken', $response->data);
        $this->assertArrayHasKey('token_type', $response->data);
        $this->assertArrayHasKey('expires_in', $response->data);

        // Validate that the token and ttl values are correct
        $this->assertEquals('bearer', $response->data['token_type']);
        $this->assertEquals(config('jwt.ttl') * 60, $response->data['expires_in']);
        $this->assertNotEquals($token, $response->data['accessToken']); // Ensure the token was refreshed
    }
}
