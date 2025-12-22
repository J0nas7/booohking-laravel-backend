<?php

namespace Tests\Unit\Services\BookingService;

use App\Models\Booking;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\BookingServiceTest\BookingServiceTest;

class StoreTest extends BookingServiceTest
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected User $user;
    protected Provider $provider;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = $this->app->make(BookingService::class);

        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->provider = Provider::factory()->create();
        $this->service = Service::factory()->create(['Service_DurationMinutes' => 60]);

        $this->actingAs($this->user, 'api'); // simulate authenticated API user
    }

    #[Test]
    public function user_can_create_booking_successfully()
    {
        $start = Carbon::tomorrow()->setHour(10);
        $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $start->toDateTimeString(),
            'Booking_EndAt' => $end->toDateTimeString(),
        ];

        $response = $this->bookingService->store($payload);

        $this->assertEquals(201, $response->status);
        $this->assertNotNull($response->data->Booking_ID);
        $this->assertDatabaseHas('bookings', [
            'Booking_ID' => $response->data->Booking_ID,
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);
    }

    #[Test]
    public function cannot_create_overlapping_booking()
    {
        $start = Carbon::tomorrow()->setHour(10);
        $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

        // Existing booking to overlap
        Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => $start,
            'Booking_EndAt' => $end,
        ]);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $start->toDateTimeString(),
            'Booking_EndAt' => $end->toDateTimeString(),
        ];

        $response = $this->bookingService->store($payload);

        $this->assertEquals(422, $response->status);
        $this->assertEquals('This time slot is already booked.', $response->error);
    }

    #[Test]
    public function multiple_non_overlapping_bookings_can_be_created()
    {
        $firstStart = Carbon::tomorrow()->setHour(10);
        $firstEnd = $firstStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => $firstStart,
            'Booking_EndAt' => $firstEnd,
        ]);

        $secondStart = Carbon::tomorrow()->setHour(12);
        $secondEnd = $secondStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $secondStart->toDateTimeString(),
            'Booking_EndAt' => $secondEnd->toDateTimeString(),
        ];

        $response = $this->bookingService->store($payload);

        $this->assertEquals(201, $response->status);
        $this->assertDatabaseHas('bookings', [
            'Booking_StartAt' => $secondStart,
            'Booking_EndAt' => $secondEnd,
        ]);
    }
}
