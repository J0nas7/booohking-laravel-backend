<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Helpers\ServiceResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Unit\Services\AuthServiceTest;

class GetUserFromCacheTest extends AuthServiceTest
{
    use RefreshDatabase;

    // No need to mock Auth, JWTAuth, or services, since they're handled by the base class
    protected function setUp(): void
    {
        parent::setUp();  // This ensures the parent class's setup is called
    }

    // Test: Cache Miss (No cached data for the user).
    public function testGetUserFromCacheCacheMiss()
    {
        // Arrange: Mock the cache to return null for a specific cache key.
        $userId = 1;
        $cacheKey = 'user:me:' . $userId;
        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(null); // Simulating a cache miss.

        // Mock a ServiceResponse with user data
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]], // Simulate user data in ServiceResponse
            message: ''
        );

        // Act: Call the method under test
        $result = $this->authService->getUserFromCache($serviceResponse);

        // Assert: It should return null for cache miss
        $this->assertNull($result);
    }

    // Test: Cache Hit (Data found in cache).
    public function testGetUserFromCacheCacheHit()
    {
        // Arrange: Mock the cache to return data.
        $userId = 1;
        $cacheKey = 'user:me:' . $userId;
        $cachedData = [
            'data' => ['user' => (object) ['id' => $userId, 'name' => 'Test User']],
            'message' => 'User data retrieved from cache',
        ];

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedData); // Simulating a cache hit.

        // Mock a ServiceResponse with user data
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]], // Simulate user data in ServiceResponse
            message: ''
        );

        // Act: Call the method under test
        $result = $this->authService->getUserFromCache($serviceResponse);

        // Assert: Check if the returned result matches the cached data structure
        $this->assertInstanceOf(ServiceResponse::class, $result);
        $this->assertEquals($cachedData['data'], $result->data);
        $this->assertEquals($cachedData['message'], $result->message);
    }

    // Test: Invalid ServiceResponse format (Missing user data).
    public function testGetUserFromCacheInvalidServiceResponse()
    {
        // Arrange: Mock a ServiceResponse with missing 'user' data.
        $serviceResponse = new ServiceResponse(
            data: [],
            message: ''
        );

        // Act: Call the method under test
        $result = $this->authService->getUserFromCache($serviceResponse);

        // Assert: It should return null as user data is missing
        $this->assertNull($result);
    }

    // Test: Cache returns invalid data (malformed cache data).
    public function testGetUserFromCacheMalformedCacheData()
    {
        // Arrange: Mock the cache to return malformed data (e.g., no 'data' key).
        $userId = 1;
        $cacheKey = 'user:me:' . $userId;
        $malformedCacheData = ['invalid_key' => 'value'];  // Missing 'data' and 'message'

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($malformedCacheData); // Simulating malformed cache data.

        // Mock a ServiceResponse with valid user data
        $serviceResponse = new ServiceResponse(
            data: ['user' => (object) ['id' => $userId]],
            message: ''
        );

        // Act: Call the method under test
        $result = $this->authService->getUserFromCache($serviceResponse);

        // Assert: It should return null due to malformed cache data
        $this->assertNull($result);
    }
}
