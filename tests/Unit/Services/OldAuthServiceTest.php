<?php

namespace Tests\Unit\Services;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Hashing\Hasher;
use App\Actions\RegisterUser\RegisterUser;
use App\Helpers\ServiceResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

// =========
// TODO OldAuthServiceTest will be split into Services/AuthServiceTest/*
// =========

class OldAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;
    protected Mailer&MockInterface $mailer;
    protected Hasher&MockInterface $hasher;
    protected RegisterUser&MockInterface $registerUser;

    // Mock the Auth and Mail facades in setUp
    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldReceive('guard')->andReturnSelf();

        $this->mailer = Mockery::mock(Mailer::class);
        $this->hasher = Mockery::mock(Hasher::class);
        $this->registerUser = Mockery::mock(RegisterUser::class);

        $this->authService = new AuthService(
            $this->mailer,
            $this->hasher,
            $this->registerUser
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ---- registerUser ----
    #[Test]
    public function it_registers_a_user_successfully()
    {
        $data = [
            'acceptTerms' => true,
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
            'User_Password_confirmation' => 'password123',
            'User_Name' => 'test'
        ];

        $expectedResult = new ServiceResponse(
            data: [
                'user' => (object)['User_Email' => 'test@example.com'],
                'token' => 'dummy-token',
                'email_status' => 'Email sent successfully.'
            ],
            message: 'User registered successfully'
        );

        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->with($data)
            ->andReturn($expectedResult);

        $result = $this->authService->registerUser($data);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_fails_to_register_a_user_with_invalid_data()
    {
        $data = [
            'acceptTerms' => false,
            'User_Email' => 'invalid-email',
            'User_Password' => 'short',
            'User_Password_confirmation' => 'mismatch',
            'User_Name' => ''
        ];

        $expectedResult = new ServiceResponse(
            errors: ['User_Email' => 'Invalid email', 'User_Password' => 'Too short'], // dummy validation errors
            status: 422,
            message: 'Validation failed'
        );

        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->with($data)
            ->andReturn($expectedResult);

        $result = $this->authService->registerUser($data);

        $this->assertSame($expectedResult, $result);
    }

    // ---- activateAccount ----
    #[Test]
    public function it_activates_the_user_email_successfully()
    {
        // ---- Arrange ----
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Email_Verification_Token' => 'valid-token',
            'User_Email_VerifiedAt' => null, // Email not yet verified
        ]);

        $validated = [
            'token' => 'valid-token',  // Valid token for this user
        ];

        // ---- Act ----
        $result = $this->authService->activateAccount($validated);

        // ---- Assert ----
        $user->refresh(); // Refresh the user to get updated fields
        $this->assertNull($user->User_Email_Verification_Token, 'Token should be cleared after activation');
        $this->assertNotNull($user->User_Email_VerifiedAt, 'Email should be verified after activation');
        $this->assertEquals('Email verified successfully', $result->message);
        $this->assertEquals('', $result->error); // No error
    }

    #[Test]
    public function it_fails_to_activate_account_with_invalid_token()
    {
        // ---- Arrange ----
        $user = User::factory()->unverified()->create([
            'User_Email' => 'test@example.com',
            'User_Email_Verification_Token' => 'valid-token',
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
        $this->assertEquals('valid-token', $user->User_Email_Verification_Token);
        $this->assertNull($user->User_Email_VerifiedAt);
    }

    #[Test]
    public function it_does_not_reactivate_email_that_is_already_verified()
    {
        // ---- Arrange ----
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Email_Verification_Token' => 'valid-token',
            'User_Email_VerifiedAt' => now(), // Already verified
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
        $this->assertTrue($user->User_Email_VerifiedAt->isSameDay(now()), 'The verification date should remain the same');
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
        $this->assertDatabaseMissing('Boo_Users', [
            'User_Email_Verification_Token' => $invalidToken,
        ]);
    }

    // ---- authenticateUser ----
    #[Test]
    public function it_authenticates_a_user_and_returns_a_token()
    {
        // ---- Arrange ----
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Password' => 'hashed-password',
            'User_Email_VerifiedAt' => now(), // IMPORTANT
        ]);

        $credentials = [
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
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
            'User_Password' => 'wrong-password',
        ];

        Auth::shouldReceive('guard->attempt')
            ->once()
            ->with($credentials)
            ->andReturn(false);

        $result = $this->authService->authenticateUser($credentials);

        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid email or password', $result->error);
    }

    // ---- sendResetToken ----
    #[Test]
    public function it_sends_a_password_reset_token()
    {
        // ---- Arrange ----
        $user = User::factory()->create(['User_Email' => 'test@example.com',]);

        $data = ['User_Email' => 'test@example.com',];

        // Mailer expectations
        $this->mailer
            ->shouldReceive('to')
            ->once()
            ->with('test@example.com')
            ->andReturnSelf();

        $this->mailer
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::type(ForgotPasswordMail::class));

        // ---- Act ----
        $result = $this->authService->sendResetToken($data);

        // ---- Assert ----
        $this->assertObjectHasProperty('data', $result);
        $this->assertObjectHasProperty('message', $result);
        $this->assertEquals('Password reset token sent.', $result->message);

        // Assert token was stored
        $this->assertNotNull(
            $user->fresh()->User_Remember_Token
        );
    }

    #[Test]
    public function it_fails_to_send_reset_token_for_invalid_email()
    {
        $data = [
            'User_Email' => 'nonexistent@example.com',
        ];

        $result = $this->authService->sendResetToken($data);

        $this->assertObjectHasProperty('errors', $result);
    }

    // ---- resetPasswordWithToken ----
    #[Test]
    public function it_resets_password_with_valid_token()
    {
        // ---- Arrange ----
        $password = "newpassword123";
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => 'ABC123ABC123ABC1',
        ]);

        $data = [
            'User_Remember_Token' => 'ABC123ABC123ABC1',
            'New_User_Password' => $password,
            'New_User_Password_confirmation' => $password,
        ];

        // Hasher expectation
        $this->hasher
            ->shouldReceive('make')
            ->once()
            ->with($password)
            ->andReturn('new-hashed-password');

        // ---- Act ----
        $result = $this->authService->resetPasswordWithToken($data);

        // ---- Assert ----
        $userFresh = User::find($user->User_ID);
        $this->assertNull($result->errors);
        $this->assertEquals('', $result->error);
        $this->assertEquals(
            'Password reset successfully',
            $result->message
        );

        $this->assertDatabaseHas('Boo_Users', [
            'User_ID' => $user->User_ID,
            'User_Remember_Token' => null,
        ]);

        // Assert it looks like a bcrypt hash
        $this->assertMatchesRegularExpression(
            '/^\$2y\$\d{2}\$[\.\/A-Za-z0-9]{53}$/',
            $userFresh->User_Password
        );
    }

    #[Test]
    public function it_fails_to_reset_password_with_invalid_token()
    {
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => 'ABC123ABC123ABC1',
        ]);

        $data = [
            'User_Remember_Token' => '123ABC123ABC123A',
            'New_User_Password' => 'newpassword123',
            'New_User_Password_confirmation' => 'newpassword123',
        ];

        $result = $this->authService->resetPasswordWithToken($data);

        $this->assertObjectHasProperty('error', $result);
        $this->assertEquals('Invalid token.', $result->error);
    }

    // ---- logoutUser ----
    #[Test]
    public function it_logs_out_the_authenticated_user()
    {
        Auth::shouldReceive('guard->logout')
            ->once()
            ->andReturn(true);

        $result = $this->authService->logoutUser();

        $this->assertObjectHasProperty('message', $result);
    }

    // ---- getAuthenticatedUser ----
    #[Test]
    public function it_returns_the_authenticated_user()
    {
        $user = User::factory()->create();

        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn($user);

        $result = $this->authService->getAuthenticatedUser();

        $this->assertEquals($user->User_Email, $result->data['user']->User_Email);
    }
}
