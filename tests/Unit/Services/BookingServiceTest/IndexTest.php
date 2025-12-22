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

class IndexTest extends BookingServiceTest
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected User $admin;
    protected User $user;
    protected Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = $this->app->make(BookingService::class);

        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->provider = Provider::factory()->create();

        Service::factory()->create(['Service_DurationMinutes' => 60]);
    }

    #[Test]
    public function admin_can_list_all_bookings()
    {
        $this->actingAs($this->admin, 'api');
        Booking::factory()->count(5)->create(['Provider_ID' => $this->provider->Provider_ID]);

        $result = $this->bookingService->index(['page' => 1, 'perPage' => 10], $this->admin);

        $this->assertEquals(5, $result->data['pagination']['total']);
        $this->assertCount(5, $result->data['data']);
    }

    #[Test]
    public function user_can_only_list_their_own_bookings()
    {
        $this->actingAs($this->user, 'api');
        Booking::factory()->create(['User_ID' => $this->user->id, 'Provider_ID' => $this->provider->Provider_ID]);
        Booking::factory()->create(['User_ID' => $this->admin->id, 'Provider_ID' => $this->provider->Provider_ID]);

        $result = $this->bookingService->index(['page' => 1, 'perPage' => 10], $this->user);

        $this->assertCount(1, $result->data['data']);
        $this->assertEquals($this->user->id, $result->data['data'][0]['User_ID']);
    }

    #[Test]
    public function returns_empty_if_no_bookings()
    {
        $this->actingAs($this->user, 'api');
        $result = $this->bookingService->index(['page' => 1, 'perPage' => 10]);

        $this->assertEquals(0, $result->data['pagination']['total']);
        $this->assertEmpty($result->data['data']);
    }
}
