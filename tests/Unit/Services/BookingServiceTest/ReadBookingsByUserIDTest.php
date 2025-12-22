<?php

namespace Tests\Unit\Services\BookingService;

use App\Models\Booking;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\BookingServiceTest\BookingServiceTest;

class ReadBookingsByUserIDTest extends BookingServiceTest
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected User $user;
    protected User $otherUser;
    protected Provider $provider;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = $this->app->make(BookingService::class);

        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->otherUser = User::factory()->create(['role' => 'ROLE_USER']);
        $this->provider = Provider::factory()->create();
        $this->service = Service::factory()->create(['Service_DurationMinutes' => 60]);
    }

    #[Test]
    public function it_returns_paginated_bookings_for_user()
    {
        // Create bookings for user
        Booking::factory()->count(5)->create([
            'User_ID' => $this->user->id,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);

        $this->actingAs($this->user, 'api');

        $validated = ['page' => 1, 'perPage' => 10];

        $response = $this->bookingService->readBookingsByUserID($validated, $this->user);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Bookings found', $response->message);
        $this->assertCount(5, $response->data['data']);
        $this->assertEquals(5, $response->data['pagination']['total']);
    }

    #[Test]
    public function it_returns_404_when_user_has_no_bookings()
    {
        $this->actingAs($this->user, 'api');

        $validated = ['page' => 1, 'perPage' => 10];

        $response = $this->bookingService->readBookingsByUserID($validated, $this->otherUser);

        $this->assertEquals(404, $response->status);
        $this->assertEquals('No bookings found for this user', $response->message);
        $this->assertEmpty($response->data['data']);
        $this->assertEquals(0, $response->data['pagination']['total']);
    }
}
