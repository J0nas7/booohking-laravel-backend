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

class ShowTest extends BookingServiceTest
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
    public function admin_can_view_any_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->id]);

        $this->actingAs($this->admin, 'api');

        $response = $this->bookingService->show($booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($booking->Booking_ID, $response->data->Booking_ID);
    }

    #[Test]
    public function user_can_view_their_own_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->id]);

        $this->actingAs($this->user, 'api');

        $response = $this->bookingService->show($booking->Booking_ID);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($booking->Booking_ID, $response->data->Booking_ID);
    }

    #[Test]
    public function user_cannot_view_others_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->admin->id]);

        $this->actingAs($this->user, 'api');

        $response = $this->bookingService->show($booking->Booking_ID);

        $this->assertEquals(403, $response->status);
        $this->assertEquals('Unauthorized', $response->error);
    }

    #[Test]
    public function show_throws_model_not_found_for_invalid_id()
    {
        $this->actingAs($this->admin, 'api');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->bookingService->show(999999); // Non-existent booking ID
    }
}
