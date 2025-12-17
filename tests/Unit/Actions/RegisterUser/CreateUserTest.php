<?php

namespace Tests\Unit\Actions\RegisterUser;

use App\Actions\RegisterUser\CreateUser;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected Hasher&MockInterface $hasher;
    protected CreateUser $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = Mockery::mock(Hasher::class);
        $this->creator = new CreateUser($this->hasher);
    }

    #[Test]
    public function it_creates_a_user_with_defaults_and_hashed_password()
    {
        $password = 'password123';
        $this->hasher
            ->shouldReceive('make')
            ->once()
            ->with($password)
            ->andReturn('hashed-password');

        $data = [
            'User_Email' => 'test@example.com',
            'User_Password' => $password,
            'User_Name' => 'John Doe',
        ];

        $user = $this->creator->execute($data);

        $this->assertDatabaseHas('Boo_Users', [
            'User_Email' => 'test@example.com',
            'User_Role' => 'ROLE_USER'
        ]);

        $this->assertNotNull($user->User_Email_Verification_Token);
        $this->assertNull($user->User_Email_VerifiedAt);

        // Assert it looks like a bcrypt hash
        $this->assertMatchesRegularExpression(
            '/^\$2y\$\d{2}\$[\.\/A-Za-z0-9]{53}$/',
            $user->User_Password
        );
    }
}
