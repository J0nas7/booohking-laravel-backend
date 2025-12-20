<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Helpers\ServiceResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\UserServiceTest;

class StoreUserTest extends UserServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Test for the 'storeUser' method to create a new user
    public function testStoreUser()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Mock the action delegate for user registration
        $this->registerUser
            ->shouldReceive('execute')
            ->with($userData)
            ->once()
            ->andReturn(new ServiceResponse(
                data: $userData,
                message: 'User created successfully',
                status: 201
            ));

        $response = $this->userService->storeUser($userData);
        $this->assertEquals('User created successfully', $response->message);
        $this->assertEquals(201, $response->status);
    }

    public function testStoreUserReturnsErrorResponseFromAction()
    {
        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->andReturn(new ServiceResponse(
                error: 'Email already exists',
                status: 422
            ));

        $response = $this->userService->storeUser(['email' => 'duplicate@example.com']);

        $this->assertEquals('Email already exists', $response->error);
        $this->assertEquals(422, $response->status);
    }

    public function testStoreUserCallsRegisterUserActionExactlyOnce()
    {
        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->andReturn(new ServiceResponse(
                message: 'User created',
                status: 201
            ));

        $this->userService->storeUser(['name' => 'Jane']);

        $this->assertTrue(true);
    }

    public function testStoreUserAlwaysReturnsServiceResponse()
    {
        $this->registerUser
            ->shouldReceive('execute')
            ->once()
            ->andReturn(new ServiceResponse(
                message: 'OK',
                status: 201
            ));

        $response = $this->userService->storeUser(['name' => 'Any']);

        $this->assertInstanceOf(ServiceResponse::class, $response);
    }
}
