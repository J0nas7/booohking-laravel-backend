<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class ActivateAccountTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    #[Test]
    public function it_activates_the_user_email_successfully()
    {
        // ---- Arrange ----
        $user = User::factory()->unverified()->create([
            'User_Email' => 'test@example.com',
            'email_verification_token' => 'valid-token',
        ]);

        $validated = [
            'token' => 'valid-token',  // Valid token for this user
        ];

        // ---- Act ----
        $result = $this->authService->activateAccount($validated);

        // ---- Assert ----
        $user->refresh(); // Refresh the user to get updated fields
        $this->assertNull($user->email_verification_token, 'Token should be cleared after activation');
        $this->assertNotNull($user->email_verified_at, 'Email should be verified after activation');
        $this->assertEquals('Email verified successfully', $result->message);
        $this->assertEquals('', $result->error); // No error
    }

    #[Test]
    public function it_fails_to_activate_account_with_invalid_token()
    {
        // ---- Arrange ----
        $user = User::factory()->unverified()->create([
            'User_Email' => 'test@example.com',
            'email_verification_token' => 'valid-token',
        ]);

        $validated = [
            'token' => 'invalid-token', // Invalid token
        ];

        // ---- Act ----
        $result = $this->authService->activateAccount($validated);

        // ---- Assert ----
        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid verification token', $result->error);
        $user->refresh();

        // The token should not be cleared and email should not be verified
        $this->assertEquals('valid-token', $user->email_verification_token);
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function it_does_not_reactivate_email_that_is_already_verified()
    {
        // ---- Arrange ----
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'email_verification_token' => 'valid-token',
            'email_verified_at' => now(), // Already verified
        ]);

        $validated = [
            'token' => 'valid-token',  // Valid token for this user
        ];

        // ---- Act ----
        $result = $this->authService->activateAccount($validated);

        // ---- Assert ----
        $this->assertEquals('Email verified successfully', $result->message);
        $this->assertEquals('', $result->error); // No error
        $user->refresh();

        // Round both timestamps to the nearest second for comparison
        $this->assertTrue($user->email_verified_at->isSameDay(now()), 'The verification date should remain the same');
    }

    #[Test]
    public function it_fails_to_activate_account_for_non_existent_user()
    {
        // ---- Arrange ----
        // Assume there are no users with this token in the database.
        $invalidToken = 'non-existent-token'; // A token that doesn't match any user in the database.

        // Create a validated array with the invalid token
        $validated = [
            'token' => $invalidToken, // Invalid token
        ];

        // ---- Act ----
        $result = $this->authService->activateAccount($validated);

        // ---- Assert ----
        // The result should contain an error, indicating that the token is invalid
        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid verification token', $result->error);

        // Since the user doesn't exist, no database changes should have been made, so no user with this token should exist
        $this->assertDatabaseMissing('users', [
            'email_verification_token' => $invalidToken,
        ]);
    }
}
