<?php

namespace Tests\Feature\Controllers;

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
        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
        $this->user = User::factory()->create(['role' => 'ROLE_USER']);

        $this->provider = Provider::factory()->create();
        $this->service = Service::factory()->create(['Service_DurationMinutes' => 60]);
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    // ==== index() ====
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

    // ==== store() ====
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
        $this->assertDatabaseHas('bookings', [
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

    // ==== show() ====
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

    // ==== destroy() ====
    #[Test]
    public function admin_can_delete_any_booking()
    {
        $booking = Booking::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/bookings/{$booking->Booking_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('bookings', ['Booking_ID' => $booking->Booking_ID]);
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

    // ==== availableSlots() ====
    #[Test]
    public function it_returns_available_slots_for_provider()
    {
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/{$this->provider->Provider_ID}/available-slots");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['date', 'start', 'end']
                ],
                'total',
                'pagination' => ['total', 'perPage', 'currentPage', 'lastPage']
            ]);
    }

    #[Test]
    public function it_excludes_booked_slots_for_provider()
    {
        $start = Carbon::tomorrow()->setHour(10);
        $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

        Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => $start,
            'Booking_EndAt' => $end,
        ]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/{$this->provider->Provider_ID}/available-slots?service_id={$this->service->Service_ID}");

        $response->assertStatus(200);
        $slots = $response->json('data');

        foreach ($slots as $slot) {
            $this->assertFalse(
                $slot['start'] === $start->format('H:i') &&
                    $slot['end'] === $end->format('H:i'),
                'Booked slot was included in available slots'
            );
        }
    }

    #[Test]
    public function it_returns_empty_when_provider_fully_booked()
    {
        $today = Carbon::tomorrow()->startOfDay();
        for ($i = 0; $i < 24; $i++) {
            $start = $today->copy()->addHours($i);
            $end = $start->copy()->addMinutes($this->service->Service_DurationMinutes);

            Booking::factory()->create([
                'Provider_ID' => $this->provider->Provider_ID,
                'Booking_StartAt' => $start,
                'Booking_EndAt' => $end,
            ]);
        }

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/{$this->provider->Provider_ID}/available-slots");

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    // ==== readBookingsByUserID() ====
    #[Test]
    public function it_reads_bookings_by_user_id()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/users/{$this->user->User_ID}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bookings found'
            ]);
    }

    #[Test]
    public function returns_404_when_user_has_no_bookings()
    {
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/bookings/users/999999"); // non-existent user

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'No query results for model [App\\Models\\User] 999999',
            ]);
    }

    #[Test]
    public function admin_can_read_any_users_bookings()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson("/api/bookings/users/{$this->user->User_ID}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bookings found'
            ]);
    }

    // ==== update() ====
    #[Test]
    public function user_can_update_their_own_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);
        $newStart = Carbon::tomorrow()->setHour(14);
        $newEnd = $newStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $newStart->toDateTimeString(),
            'Booking_EndAt' => $newEnd->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->putJson("/api/bookings/{$booking->Booking_ID}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'Booking_ID' => $booking->Booking_ID,
                'User_ID' => $this->user->User_ID
            ]);

        $this->assertDatabaseHas('bookings', [
            'Booking_ID' => $booking->Booking_ID,
            'Booking_StartAt' => $newStart,
            'Booking_EndAt' => $newEnd,
        ]);
    }

    #[Test]
    public function user_cannot_update_others_booking()
    {
        $booking = Booking::factory()->create(['User_ID' => $this->admin->User_ID]);
        $newStart = Carbon::tomorrow()->setHour(14);
        $newEnd = $newStart->copy()->addMinutes($this->service->Service_DurationMinutes);

        $payload = [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => $newStart->toDateTimeString(),
            'Booking_EndAt' => $newEnd->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->putJson("/api/bookings/{$booking->Booking_ID}", $payload);

        $response->assertStatus(403);
    }

    #[Test]
    public function cannot_update_booking_to_overlapping_slot()
    {
        $existing = Booking::factory()->create([
            'Provider_ID' => $this->provider->Provider_ID,
            'Booking_StartAt' => Carbon::tomorrow()->setHour(10),
            'Booking_EndAt' => Carbon::tomorrow()->setHour(11),
        ]);

        $booking = Booking::factory()->create(['User_ID' => $this->user->User_ID]);

        $payload = [
            'User_ID' => $this->user->User_ID,
            'Provider_ID' => $this->provider->Provider_ID,
            'Service_ID' => $this->service->Service_ID,
            'Booking_StartAt' => Carbon::tomorrow()->setHour(10)->toDateTimeString(),
            'Booking_EndAt' => Carbon::tomorrow()->setHour(11)->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->putJson("/api/bookings/{$booking->Booking_ID}", $payload);

        $response->assertStatus(422)
            ->assertJson(['error' => 'This time slot is already booked.']);
    }
}
