<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class ResetPasswordWithTokenTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

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
}
