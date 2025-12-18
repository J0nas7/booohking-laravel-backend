<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Helpers\ServiceResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Unit\Services\AuthServiceTest;

class ForgetUserFromCacheTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    public function testForgetUserFromCacheWithValidUserData()
    {
        // Arrange: Create a ServiceResponse with valid user data
        $userId = 1;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['User_ID' => $userId]],
            message: 'User data fetched successfully.'
        );

        $cacheKey = 'user:me:' . $userId;

        // Mock Cache::forget to ensure it's called
        Cache::shouldReceive('forget')
            ->once()
            ->with($cacheKey)
            ->andReturn(true);  // Simulating cache forget success

        // Act: Call the forgetUserFromCache method
        $this->authService->forgetUserFromCache($serviceResponse);

        // Assert: Ensure that Cache::forget was called with the correct key
        Cache::shouldHaveReceived('forget')
            ->once()
            ->with($cacheKey);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }

    public function testForgetUserFromCacheWithMissingUserData()
    {
        // Arrange: Create a ServiceResponse without user data
        $serviceResponse = new ServiceResponse(
            data: [],  // No 'user' data
            message: 'No user data found.'
        );

        // Mock the Cache facade directly
        $cacheMock = Mockery::mock('alias:Cache');

        // Mock Cache::forget to ensure it doesn't get called
        $cacheMock->shouldNotReceive('forget');

        // Act: Call the forgetUserFromCache method
        $this->authService->forgetUserFromCache($serviceResponse);

        // Assert: Ensure that Cache::forget was never called
        $cacheMock->shouldNotHaveReceived('forget');

        // This serves as a placeholder to satisfy PHPUnit
        $this->assertTrue(true);
    }

    public function testForgetUserFromCacheFailure()
    {
        // Arrange: Create a ServiceResponse with valid user data
        $userId = 2;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['User_ID' => $userId]],
            message: 'User data fetched successfully.'
        );

        $cacheKey = 'user:me:' . $userId;

        // Simulate a failure in Cache::forget() (returns false)
        Cache::shouldReceive('forget')
            ->once()
            ->with($cacheKey)
            ->andReturn(false);  // Simulating cache forget failure

        // Act: Call the forgetUserFromCache method
        $this->authService->forgetUserFromCache($serviceResponse);

        // Assert: Ensure Cache::forget was called with the correct cache key
        Cache::shouldHaveReceived('forget')
            ->once()
            ->with($cacheKey);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }

    public function testForgetUserFromCacheWithMalformedData()
    {
        // Arrange: Create a ServiceResponse with invalid user data (no User_ID)
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['User_ID' => null]],  // Invalid User_ID
            message: 'Invalid user data.'
        );

        // Mock the Cache facade directly
        $cacheMock = Mockery::mock('alias:Cache');

        // Mock Cache::forget to ensure it doesn't get called
        $cacheMock->shouldNotReceive('forget');

        // Act: Call the forgetUserFromCache method
        $this->authService->forgetUserFromCache($serviceResponse);

        // Assert: Ensure that Cache::forget was never called due to invalid user data
        $cacheMock->shouldNotHaveReceived('forget');

        // This serves as a placeholder to satisfy PHPUnit
        $this->assertTrue(true);
    }

    public function testForgetUserFromCacheWithEmptyMessage()
    {
        // Arrange: Create a ServiceResponse with valid user data but an empty message
        $userId = 3;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['User_ID' => $userId]],
            message: ''  // Empty message
        );

        $cacheKey = 'user:me:' . $userId;

        // Mock Cache::forget to ensure it's called
        Cache::shouldReceive('forget')
            ->once()
            ->with($cacheKey)
            ->andReturn(true);  // Simulating cache forget success

        // Act: Call the forgetUserFromCache method
        $this->authService->forgetUserFromCache($serviceResponse);

        // Assert: Ensure Cache::forget was called with the correct cache key
        Cache::shouldHaveReceived('forget')
            ->once()
            ->with($cacheKey);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }
}
