<?php

namespace Tests\Unit\Actions\SendResetToken;

use App\Actions\SendResetToken\GenerateResetToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GenerateResetTokenTest extends SendResetTokenTest
{
    use RefreshDatabase;

    protected GenerateResetToken $tokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenGenerator = new GenerateResetToken();
    }

    #[Test]
    public function it_generates_and_saves_a_reset_token()
    {
        $user = User::factory()->create(['User_Email' => 'test@example.com']);

        $token = $this->tokenGenerator->execute($user);

        $this->assertEquals(16, strlen($token));
        $this->assertDatabaseHas('Boo_Users', [
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => $token,
        ]);
    }

    #[Test]
    public function it_overwrites_existing_token()
    {
        $user = User::factory()->create([
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => 'old-token',
        ]);

        $token = $this->tokenGenerator->execute($user);

        $this->assertNotEquals('old-token', $token);
        $this->assertDatabaseHas('Boo_Users', [
            'User_Email' => 'test@example.com',
            'User_Remember_Token' => $token,
        ]);
    }
}
