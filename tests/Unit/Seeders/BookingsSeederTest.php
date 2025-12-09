<?php

namespace Tests\Unit\Seeders;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\BookingsSeeder;
use PHPUnit\Framework\Attributes\Test;

class BookingsSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_bookings_table_correctly()
    {
        $databasePrefix = "Boo_";

        // Ensure required related models exist
        $user = User::factory()->create(['User_Email' => 'jonas-usr@booohking.com']);
        $service = Service::factory()->create(['Service_DurationMinutes' => 60]);
        $provider = Provider::factory()->create();

        // Run the seeder
        $this->seed(BookingsSeeder::class);

        // Assert at least 1 booking exists
        $this->assertDatabaseCount($databasePrefix . 'Bookings', 1);

        // Assert specific booking record exists
        $start = Carbon::tomorrow()->setHour(10)->setMinute(0)->setSecond(0);
        $end = $start->copy()->addMinutes($service->Service_DurationMinutes);

        $this->assertDatabaseHas($databasePrefix . 'Bookings', [
            'User_ID' => $user->User_ID,
            'Provider_ID' => $provider->Provider_ID,
            'Service_ID' => $service->Service_ID,
            'Booking_Status' => 'booked',
            'Booking_StartAt' => $start->format('Y-m-d H:i:s'),
            'Booking_EndAt' => $end->format('Y-m-d H:i:s'),
        ]);
    }
}
