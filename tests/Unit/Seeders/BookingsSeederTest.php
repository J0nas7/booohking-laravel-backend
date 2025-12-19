<?php

namespace Tests\Unit\Seeders;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
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
        // Create test data
        $service = Service::factory()->create(['Service_DurationMinutes' => 60]);
        $provider = Provider::factory()->forService($service)->create();

        // Run the seeder
        $this->seed(BookingsSeeder::class);

        // Assert at least one booking exists
        $this->assertDatabaseHas('bookings', [
            'Provider_ID' => $provider->Provider_ID,
            'Service_ID' => $service->Service_ID,
            'Booking_Status' => 'booked',
        ]);

        // Assert that some bookings exist overall
        $this->assertGreaterThan(0, Booking::count());
    }
}
