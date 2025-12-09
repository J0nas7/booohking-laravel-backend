<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clear any test now
        Carbon::setTestNow();
    }

    #[Test]
    public function returns_slots_for_provider_with_working_hours()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();

        // working hours: tomorrow 09:00 - 17:00 (DayOfWeek of tomorrow)
        $tomorrow = Carbon::today()->addDay();
        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $tomorrow->dayOfWeek,
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '17:00',
        ]);

        $slots = BookingService::generateAvailableSlots($provider, 2, 30);

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots, 'Expected slots for provider with working hours');

        // Each slot should have date, start, end
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('date', $slot);
            $this->assertArrayHasKey('start', $slot);
            $this->assertArrayHasKey('end', $slot);

            // date is within next 2 days
            $date = Carbon::parse($slot['date']);
            $this->assertTrue(
                $date->betweenIncluded(Carbon::today(), Carbon::today()->addDays(1)),
                "Slot date {$slot['date']} not within expected range"
            );
        }
    }

    #[Test]
    public function excludes_already_booked_slots()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();
        $user = User::factory()->create();
        $service = Service::factory()->create(['Service_DurationMinutes' => 30]);

        // tomorrow working hours
        $tomorrow = Carbon::today()->addDay();
        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $tomorrow->dayOfWeek,
            'PWH_StartTime' => '10:00',
            'PWH_EndTime' => '12:00',
        ]);

        // Create an existing booking at 10:30 - (30m)
        $bookedStart = $tomorrow->copy()->setTime(10, 30, 0);
        Booking::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'User_ID' => $user->User_ID,
            'Service_ID' => $service->Service_ID,
            'Booking_StartAt' => $bookedStart,
        ]);

        $slots = BookingService::generateAvailableSlots($provider, 2, 30);

        // Ensure none of the returned slots equals the booked slot
        foreach ($slots as $slot) {
            $this->assertFalse(
                $slot['date'] === $bookedStart->toDateString() && $slot['start'] === $bookedStart->format('H:i'),
                'Booked slot appears in available slots'
            );
        }
    }

    #[Test]
    public function respects_service_duration_when_generating_slots()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();
        $service = Service::factory()->create(['Service_DurationMinutes' => 60]);

        // working hours tomorrow 09:00 - 13:00
        $tomorrow = Carbon::today()->addDay();
        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $tomorrow->dayOfWeek,
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '13:00',
        ]);

        $slots = BookingService::generateAvailableSlots($provider, 2, 60, $service->Service_ID);

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);

        // Each slot must be 60 minutes (end - start)
        foreach ($slots as $slot) {
            $start = Carbon::createFromFormat('Y-m-d H:i', $slot['date'] . ' ' . $slot['start']);
            $end = Carbon::createFromFormat('Y-m-d H:i', $slot['date'] . ' ' . $slot['end']);
            $this->assertEquals(60, $start->diffInMinutes($end));
        }
    }

    #[Test]
    public function returns_empty_when_provider_has_no_working_hours()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();

        $slots = BookingService::generateAvailableSlots($provider, 7, 30);

        $this->assertIsArray($slots);
        $this->assertEmpty($slots, 'Expected no slots when provider has no working hours');
    }

    #[Test]
    public function handles_multiple_working_periods_per_day()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();
        $tomorrow = Carbon::today()->addDay();

        // morning 09:00-12:00
        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $tomorrow->dayOfWeek,
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '12:00',
        ]);

        // afternoon 13:00-17:00
        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $tomorrow->dayOfWeek,
            'PWH_StartTime' => '13:00',
            'PWH_EndTime' => '17:00',
        ]);

        $slots = BookingService::generateAvailableSlots($provider, 2, 30);

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);

        $hasMorning = false;
        $hasAfternoon = false;

        foreach ($slots as $slot) {
            $hour = (int) explode(':', $slot['start'])[0];
            if ($hour >= 9 && $hour < 12) {
                $hasMorning = true;
            }
            if ($hour >= 13 && $hour < 17) {
                $hasAfternoon = true;
            }
        }

        $this->assertTrue($hasMorning, 'Morning slots missing');
        $this->assertTrue($hasAfternoon, 'Afternoon slots missing');

        // ensure there's a gap around 12:00 - 13:00
        $hasNoonSlot = collect($slots)->contains(function ($s) {
            return $s['start'] === '12:00' || $s['start'] === '12:30';
        });
        $this->assertFalse($hasNoonSlot, 'Slots found in the defined break period');
    }

    #[Test]
    public function only_returns_future_slots()
    {
        // set now to 11:15 so earlier slots of the day are in the past
        Carbon::setTestNow(Carbon::parse('2025-01-02 11:15:00'));

        User::factory()->create();
        $provider = Provider::factory()->create();
        $today = Carbon::today();

        ProviderWorkingHour::factory()->create([
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $today->dayOfWeek,
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '14:00',
        ]);

        $slots = BookingService::generateAvailableSlots($provider, 1, 30);

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);

        // all returned slots must be after now
        foreach ($slots as $slot) {
            $slotStart = Carbon::createFromFormat('Y-m-d H:i', $slot['date'] . ' ' . $slot['start']);
            $this->assertTrue($slotStart->greaterThan(Carbon::now()->subSecond()));
        }
    }
}
