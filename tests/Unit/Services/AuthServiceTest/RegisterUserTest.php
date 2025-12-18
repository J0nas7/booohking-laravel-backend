<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Helpers\ServiceResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

class RegisterUserTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    #[Test]
    public function it_registers_a_user_successfully()
    {
        $data = [
            'acceptTerms' => true,
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
            'User_Password_confirmation' => 'password123',
            'User_Name' => 'test'
        ];

        $expectedResult = new ServiceResponse(
            data: [
                'user' => (object)['User_Email' => 'test@example.com'],
                'token' => 'dummy-token',
                'email_status' => 'Email sent successfully.'
            ],
            message: 'User registered successfully'
        );

        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->with($data)
            ->andReturn($expectedResult);

        $result = $this->authService->registerUser($data);

        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function it_fails_to_register_a_user_with_invalid_data()
    {
        $data = [
            'acceptTerms' => false,
            'User_Email' => 'invalid-email',
            'User_Password' => 'short',
            'User_Password_confirmation' => 'mismatch',
            'User_Name' => ''
        ];

        $expectedResult = new ServiceResponse(
            errors: ['User_Email' => 'Invalid email', 'User_Password' => 'Too short'], // dummy validation errors
            status: 422,
            message: 'Validation failed'
        );

        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->with($data)
            ->andReturn($expectedResult);

        $result = $this->authService->registerUser($data);

        $this->assertSame($expectedResult, $result);
    }
}
