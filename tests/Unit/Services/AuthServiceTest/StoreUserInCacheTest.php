<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Helpers\ServiceResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Unit\Services\AuthServiceTest;

class StoreUserInCacheTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    public function testStoreUserInCache()
    {
        // Arrange: Create a valid ServiceResponse with user data.
        $userId = 1;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]],
            message: 'User data fetched successfully.'
        );

        $cacheKey = 'user:me:' . $userId;
        $cacheData = [
            'data' => $serviceResponse->data,
            'message' => $serviceResponse->message,
        ];

        // Expect Cache::put() to be called with the correct key and data
        Cache::shouldReceive('put')
            ->once()
            ->with($cacheKey, $cacheData, 900) // Default cache time of 900 seconds (15 minutes)
            ->andReturn(true);

        // Act: Call the storeUserInCache method
        $this->authService->storeUserInCache($serviceResponse);

        // Assert: Verify Cache::put() was called with the correct parameters.
        Cache::shouldHaveReceived('put')
            ->once()
            ->with($cacheKey, $cacheData, 900);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }

    public function testStoreUserInCacheMissingUserData()
    {
        // Arrange: Create a ServiceResponse without user data
        $serviceResponse = new ServiceResponse(
            data: [],  // No 'user' data
            message: 'No user data found.'
        );

        // Mock the Cache facade directly
        $cacheMock = Mockery::mock('alias:Cache');

        // We expect that Cache::put() will **not** be called
        $cacheMock->shouldNotReceive('put');

        // Act: Call the storeUserInCache method
        $this->authService->storeUserInCache($serviceResponse);

        // Assert: Verify that Cache::put was never called
        $cacheMock->shouldNotHaveReceived('put');

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }

    public function testStoreUserInCacheWithCustomCacheTime()
    {
        // Arrange: Create a valid ServiceResponse with user data
        $userId = 2;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]],
            message: 'User data fetched successfully.'
        );

        $cacheKey = 'user:me:' . $userId;
        $cacheData = [
            'data' => $serviceResponse->data,
            'message' => $serviceResponse->message,
        ];

        $customCacheTime = 1800; // 30 minutes

        // Expect Cache::put() to be called with the correct cache time
        Cache::shouldReceive('put')
            ->once()
            ->with($cacheKey, $cacheData, $customCacheTime)
            ->andReturn(true);

        // Act: Call the storeUserInCache method with a custom cache time
        $this->authService->storeUserInCache($serviceResponse, $customCacheTime);

        // Assert: Verify Cache::put() was called with the correct cache time
        Cache::shouldHaveReceived('put')
            ->once()
            ->with($cacheKey, $cacheData, $customCacheTime);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }

    public function testStoreUserInCacheCacheFailure()
    {
        // Arrange: Create a valid ServiceResponse with user data
        $userId = 3;
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]],
            message: 'User data fetched successfully.'
        );

        $cacheKey = 'user:me:' . $userId;
        $cacheData = [
            'data' => $serviceResponse->data,
            'message' => $serviceResponse->message,
        ];

        // Simulate a cache failure (e.g., returning false from Cache::put())
        Cache::shouldReceive('put')
            ->once()
            ->with($cacheKey, $cacheData, 900)
            ->andReturn(false);  // Simulating cache failure

        // Act: Call the storeUserInCache method
        $this->authService->storeUserInCache($serviceResponse);

        // Assert: Ensure Cache::put() was called with correct parameters
        Cache::shouldHaveReceived('put')
            ->once()
            ->with($cacheKey, $cacheData, 900);

        $this->assertTrue(true); // This serves as a placeholder to satisfy PHPUnit
    }
}
