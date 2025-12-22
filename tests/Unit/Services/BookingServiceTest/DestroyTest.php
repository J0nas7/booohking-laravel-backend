<?php

namespace Tests\Unit\Services\BookingService;

use App\Models\Booking;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Services\BookingServiceTest\BookingServiceTest;

class DestroyTest extends BookingServiceTest
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
    public function user_can_delete_their_own_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->bookingService->destroy($booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Deleted successfully', $response->message);

        $this->assertSoftDeleted('bookings', [
            'Booking_ID' => $booking->Booking_ID,
        ]);
    }

    #[Test]
    public function user_cannot_delete_others_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->admin->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->bookingService->destroy($booking->Booking_ID);

        $this->assertEquals(403, $response->status);
        $this->assertEquals('Unauthorized', $response->error);

        $this->assertDatabaseHas('bookings', [
            'Booking_ID' => $booking->Booking_ID,
        ]);
    }

    #[Test]
    public function admin_can_delete_any_booking()
    {
        $booking = Booking::factory()->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->admin, 'api');

        $response = $this->bookingService->destroy($booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Deleted successfully', $response->message);

        $this->assertSoftDeleted('bookings', [
            'Booking_ID' => $booking->Booking_ID,
        ]);
    }
}
