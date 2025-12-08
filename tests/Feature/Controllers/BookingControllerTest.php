<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Provider $provider;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed users, provider, and service
        $this->admin = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
        $this->user = User::factory()->create(['User_Role' => 'ROLE_USER']);

        $this->provider = Provider::factory()->create();
        $this->service = Service::factory()->create(['Service_DurationMinutes' => 60]);
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    #[Test]
    public function admin_can_list_all_bookings()
    {
        Booking::factory()->count(3)->create(['Provider_ID' => $this->provider->Provider_ID]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/bookings');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    #[Test]
    public function user_can_only_see_their_own_bookings()
    {
        Booking::factory()->create(['User_ID' => $this->user->User_ID, 'Provider_ID' => $this->provider->Provider_ID]);
        Booking::factory()->create(['User_ID' => $this->admin->User_ID, 'Provider_ID' => $this->provider->Provider_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/bookings');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->user->User_ID, $response->json('data')[0]['User_ID']);
    }

    #[Test]
    public function user_can_create_booking()
    {
        $start = Carbon::tomorrow()->setHour(10)->setMinute(0);
        $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $start->toDateTimeString(),
            'Booking_EndAt' => $end->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->postJson('/api/bookings', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('Boo_Bookings', [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
        ]);
    }

    #[Test]
    public function user_cannot_create_overlapping_booking()
    {
        $start = Carbon::tomorrow()->setHour(10);
        $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

        Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => $start,
            'Booking_EndAt' => $end,
        ]);

        $payload = [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $start->toDateTimeString(),
            'Booking_EndAt' => $end->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->postJson('/api/bookings', $payload);

        $response->assertStatus(422)
            ->assertJson(['error' => 'This time slot is already booked.']);
    }

    #[Test]
    public function user_can_view_own_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(200);
        $this->assertEquals($booking->Booking_ID, $response->json('Booking_ID'));
    }

    #[Test]
    public function user_cannot_view_others_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->admin->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_delete_any_booking()
    {
        $booking = Booking::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('Boo_Bookings', ['Booking_ID' => $booking->Booking_ID]);
    }

    #[Test]
    public function user_can_delete_their_own_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->deleteJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);
    }

    #[Test]
    public function user_cannot_delete_others_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->admin->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->deleteJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(403);
    }
}
