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
use Tests\TestCase;
use Tests\Unit\Services\BookingServiceTest\BookingServiceTest;

class UpdateTest extends BookingServiceTest
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected User $user;
    protected User $admin;
    protected Provider $provider;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = $this->app->make(BookingService::class);

        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
        $this->provider = Provider::factory()->create();
        $this->service = Service::factory()->create(['Service_DurationMinutes' => 60]);
    }

    #[Test]
    public function user_can_update_their_own_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $newStart = Carbon::tomorrow()->setHour(14);
        $newEnd = $newStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $newStart->toDateTimeString(),
            'Booking_EndAt' => $newEnd->toDateTimeString(),
        ];

        $response = $this->bookingService->update($payload, $booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($booking->Booking_ID, $response->data->Booking_ID);

        $this->assertDatabaseHas('bookings', [
            'Booking_ID' => $booking->Booking_ID,
            'Booking_StartAt' => $newStart,
            'Booking_EndAt' => $newEnd,
        ]);
    }

    #[Test]
    public function user_cannot_update_others_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->admin->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $newStart = Carbon::tomorrow()->setHour(14);
        $newEnd = $newStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $newStart->toDateTimeString(),
            'Booking_EndAt' => $newEnd->toDateTimeString(),
        ];

        $response = $this->bookingService->update($payload, $booking->Booking_ID);

        $this->assertEquals(403, $response->status);
        $this->assertEquals('Unauthorized', $response->error);
    }

    #[Test]
    public function cannot_update_booking_to_overlapping_slot()
    {
        $existing = Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => Carbon::tomorrow()->setHour(10),
            'Booking_EndAt' => Carbon::tomorrow()->setHour(11),
        ]);

        $booking = Booking::factory()->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => Carbon::tomorrow()->setHour(10)->toDateTimeString(),
            'Booking_EndAt' => Carbon::tomorrow()->setHour(11)->toDateTimeString(),
        ];

        $response = $this->bookingService->update($payload, $booking->Booking_ID);

        $this->assertEquals(422, $response->status);
        $this->assertEquals('This time slot is already booked.', $response->error);
    }

    #[Test]
    public function admin_can_update_any_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->admin, 'api');

        $newStart = Carbon::tomorrow()->setHour(15);
        $newEnd = $newStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $newStart->toDateTimeString(),
            'Booking_EndAt' => $newEnd->toDateTimeString(),
        ];

        $response = $this->bookingService->update($payload, $booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($booking->Booking_ID, $response->data->Booking_ID);
        $this->assertDatabaseHas('bookings', [
            'Booking_ID' => $booking->Booking_ID,
            'Booking_StartAt' => $newStart,
            'Booking_EndAt' => $newEnd,
        ]);
    }
}
