<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class GetAuthenticatedUserTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    #[Test]
    public function it_returns_the_authenticated_user()
    {
        $user = User::factory()->create();

        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn($user);

        $result = $this->authService->getAuthenticatedUser();

        $this->assertEquals($user->User_Email, $result->data['user']->User_Email);
    }
}
