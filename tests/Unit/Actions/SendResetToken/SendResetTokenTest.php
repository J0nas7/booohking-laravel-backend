<?php

namespace Tests\Unit\Actions\SendResetToken;

use App\Actions\SendResetToken\SendResetToken;
use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendResetTokenTest extends TestCase
{
    use RefreshDatabase;

    protected Mailer&MockInterface $mailMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Mailer
        $this->mailMock = Mockery::mock(Mailer::class);
    }

    #[Test]
    public function it_sends_email_successfully()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $service = new SendResetToken();

        $result = $service->execute([
            'email' => $user->email,
        ]);

        $this->assertEquals(200, $result->status);
        $this->assertEquals('If an account with that email exists, a password reset link has been sent.', $result->message);
    }

    #[Test]
    public function it_handles_mail_transport_failure_gracefully(): void
    {
        Log::shouldReceive('error')->once();

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new \Symfony\Component\Mailer\Exception\TransportException('SMTP down'));

        $service = new SendResetToken();

        $result = $service->execute([
            'email' => 'test@example.com',
        ]);

        $this->assertEquals(
            'We could not send the password reset email at this time. Please try again later.',
            $result->error
        );

        $this->assertEquals(503, $result->status);
    }

    #[Test]
    public function it_returns_rate_limit_error_when_reset_is_throttled(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $service = new SendResetToken();

        $result = $service->execute([
            'email' => 'test@example.com',
        ]);

        $this->assertEquals(429, $result->status);
        $this->assertEquals(
            'Please wait before requesting another password reset email.',
            $result->error
        );
    }
}
