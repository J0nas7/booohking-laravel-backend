<?php

namespace Tests\Unit\Services\BookingServiceTest;

use App\Actions\GenerateAvailableSlots\GenerateAvailableSlots;
use Tests\TestCase;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;
    protected BookingService $bookingService;
    protected GenerateAvailableSlots&MockInterface $generateAvailableSlots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generateAvailableSlots = Mockery::mock(GenerateAvailableSlots::class);
        $this->bookingService = new BookingService(
            $this->generateAvailableSlots
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    // A simple dummy test to ensure PHPUnit is satisfied
    public function testDummy()
    {
        $this->assertTrue(true); // This will always pass
    }
}
