<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class SendResetTokenTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

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
}
