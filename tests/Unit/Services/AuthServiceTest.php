<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Hashing\Hasher;
use App\Actions\RegisterUser\RegisterUser;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Contracts\Auth\Guard;

class AuthServiceTest extends TestCase
{
    protected AuthService $authService;
    protected Mailer&MockInterface $mailer;
    protected Hasher&MockInterface $hasher;
    protected RegisterUser&MockInterface $registerUser;
    protected Guard&MockInterface $guard;
    protected JWTAuth&MockInterface $jwtAuthFacade;

    // Common setUp for all AuthService related tests
    protected function setUp(): void
    {
        parent::setUp();
        // Auth::shouldReceive('guard')->andReturnSelf();

        // Mocking common dependencies for AuthService
        $this->mailer = Mockery::mock(Mailer::class);
        $this->hasher = Mockery::mock(Hasher::class);
        $this->registerUser = Mockery::mock(RegisterUser::class);

        // Initialize AuthService with mocked dependencies
        $this->authService = new AuthService(
            $this->mailer,
            $this->hasher,
            $this->registerUser
        );
    }

    // Common tearDown for all AuthService related tests
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
