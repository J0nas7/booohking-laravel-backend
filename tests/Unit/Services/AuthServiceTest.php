<?php

namespace Tests\Feature\Services;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;
    use AuthService;

    // Mock the Auth and Mail facades in setUp
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // Fake mail sending so no real email is sent
        Auth::shouldReceive('guard')->andReturnSelf();
    }

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

        $result = $this->registerUser($data);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('User was created.', $result['message']);
        $this->assertDatabaseHas('Boo_Users', ['User_Email' => 'test@example.com']);

        // Assert that a welcome email was "sent"
        Mail::assertSent(WelcomeEmail::class, function (WelcomeEmail $mail) use ($data) {
            return $mail->hasTo($data['User_Email']);
        });
    }

    #[Test]
    public function it_fails_to_register_a_user_with_invalid_data()
    {
        $data = [
            'acceptTerms' => false,
            'userEmail' => 'invalid-email',
            'userPassword' => 'short',
            'userFirstname' => '',
            'userSurname' => '',
        ];

        $result = $this->registerUser($data);

        $this->assertArrayHasKey('errors', $result);
    }

    #[Test]
    public function it_authenticates_a_user_and_returns_a_token()
    {
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Password' => Hash::make('password123'),
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

        $result = $this->authenticateUser($credentials);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('Login was successful', $result['message']);
        $this->assertEquals('fake-token', $result['data']['accessToken']);
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

        $result = $this->authenticateUser($credentials);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid email or password', $result['error']);
    }

    #[Test]
    public function it_sends_a_password_reset_token()
    {
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
        ]);

        $data = [
            'User_Email' => 'test@example.com',
        ];

        // This will match the expectation set above
        $result = $this->sendResetToken($data);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('Password reset token sent.', $result['message']);

        // Assert that the ForgotPasswordMail mailable was sent
        Mail::assertSent(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->User_Email)
                && !empty($mail->token); // token was set
        });
    }

    #[Test]
    public function it_fails_to_send_reset_token_for_invalid_email()
    {
        $data = [
            'User_Email' => 'nonexistent@example.com',
        ];

        $result = $this->sendResetToken($data);

        $this->assertArrayHasKey('errors', $result);
    }

    #[Test]
    public function it_resets_password_with_valid_token()
    {
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => 'ABC123ABC123ABC1',
        ]);

        $data = [
            'User_Remember_Token' => 'ABC123ABC123ABC1',
            'New_User_Password' => 'newpassword123',
            'New_User_Password_confirmation' => 'newpassword123',
        ];

        $result = $this->resetPasswordWithToken($data);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('Password has been reset successfully.', $result['message']);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->User_Password));
        $this->assertNull($user->fresh()->User_Remember_Token);
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

        $result = $this->resetPasswordWithToken($data);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid token.', $result['error']);
    }

    #[Test]
    public function it_logs_out_the_authenticated_user()
    {
        Auth::shouldReceive('guard->logout')
            ->once()
            ->andReturn(true);

        $result = $this->logoutUser();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_the_authenticated_user()
    {
        $user = User::factory()->create();

        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn($user);

        $result = $this->getAuthenticatedUser();

        $this->assertEquals($user->User_Email, $result->User_Email);
    }
}
