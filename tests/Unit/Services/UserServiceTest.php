<?php

namespace Tests\Unit\Services;

use App\Services\UserService;
use App\Actions\RegisterUser\RegisterUser;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;
    protected Mailer&MockInterface $mailer;
    protected Hasher&MockInterface $hasher;
    protected RegisterUser&MockInterface $registerUser;

    // Common setUp for all UserService related tests
    protected function setUp(): void
    {
        parent::setUp();

        // Mocking common dependencies for UserService
        $this->mailer = Mockery::mock(Mailer::class);
        $this->hasher = Mockery::mock(Hasher::class);
        $this->registerUser = Mockery::mock(RegisterUser::class);

        // Initialize UserService with mocked dependencies
        $this->userService = new UserService(
            $this->mailer,
            $this->hasher,
            $this->registerUser
        );
    }

    // Common tearDown for all UserService related tests
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // A simple dummy test to ensure PHPUnit is satisfied
    public function testDummy()
    {
        $this->assertTrue(true); // This will always pass
    }
}
