<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Notifications\ForgotPasswordNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class ResetPasswordWithTokenTest extends AuthServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = new AuthService(
            app()->make(\Illuminate\Contracts\Mail\Mailer::class),
            app()->make(\Illuminate\Contracts\Hashing\Hasher::class),
            $this->registerUser,
            $this->sendResetToken
        );
    }

    #[Test]
    public function it_resets_password_with_valid_token(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'User_Email' => 'test@example.com',
        ]);

        // Send reset link (this generates a token and "sends" email)
        Password::sendResetLink(['email' => $user->User_Email]);

        // Assert notification was "sent"
        Notification::assertSentTo(
            [$user],
            ForgotPasswordNotification::class,
            function ($notification, $channels) use (&$token) {
                $token = $notification->getToken();
                return in_array('mail', $channels);
            }
        );

        $newPassword = 'newpassword123';

        $data = [
            'email' => $user->User_Email,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        // Act
        $result = $this->authService->resetPasswordWithToken($data);

        // Refresh user from DB
        $userFresh = User::find($user->User_ID);

        // Assert
        $this->assertEquals('', $result->error);
        $this->assertEquals('Password reset successfully.', $result->message);
        $this->assertTrue(Hash::check($newPassword, $userFresh->User_Password));
        $this->assertNull($userFresh->User_Remember_Token);
    }

    #[Test]
    public function it_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'User_Email' => 'test@example.com',
        ]);

        $data = [
            'email' => $user->User_Email,
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $result = $this->authService->resetPasswordWithToken($data);

        $this->assertNotEmpty($result->error);
        $this->assertEquals('The reset token is invalid or has expired.', $result->error);
    }
}
