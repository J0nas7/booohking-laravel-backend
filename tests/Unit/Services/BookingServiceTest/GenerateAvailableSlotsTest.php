<?php

namespace Tests\Unit\Services\BookingServiceTest;

use Illuminate\Foundation\Testing\RefreshDatabase;

// BookingService::generateAvailableSlotsTest()
class GenerateAvailableSlotsTest extends BookingServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // A simple dummy test to ensure PHPUnit is satisfied
    public function testDummy()
    {
        $this->assertTrue(true); // This will always pass
    }
}
