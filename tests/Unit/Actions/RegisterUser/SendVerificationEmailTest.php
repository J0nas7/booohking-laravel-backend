<?php

namespace Tests\Unit\Actions\RegisterUser;

use App\Actions\RegisterUser\SendVerificationEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class SendVerificationEmailTest extends RegisterUserTest
{
    use RefreshDatabase;
    protected Mailer&MockInterface $mailMock;
    protected SendVerificationEmail $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Mailer
        $this->mailMock = Mockery::mock(Mailer::class);

        $this->mailer = new SendVerificationEmail($this->mailMock);
    }

    #[Test]
    public function it_sends_email_successfully()
    {
        $user = User::factory()->create([
            'email_verification_token' => '1234567890abcdef'
        ]);

        // Expect Mailer to receive "to()->send()"
        $this->mailMock
            ->shouldReceive('to')
            ->once()
            ->with($user->User_Email)
            ->andReturnSelf();

        $this->mailMock
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::type(WelcomeEmail::class));

        $result = $this->mailer->execute($user);

        $this->assertEquals('Email sent successfully.', $result['status']);
        $this->assertEquals('', $result['token']);
    }

    #[Test]
    public function it_handles_mail_failure_gracefully()
    {
        $user = User::factory()->create([
            'email_verification_token' => '1234567890abcdef'
        ]);

        // Make the mailer throw
        $this->mailMock
            ->shouldReceive('to->send')
            ->once()
            ->andThrow(new \Exception('SMTP error'));

        Log::shouldReceive('error')->once();

        $result = $this->mailer->execute($user);

        $this->assertStringContainsString('Failed to send email', $result['status']);
        $this->assertEquals($user->email_verification_token, $result['token']);
    }
}
