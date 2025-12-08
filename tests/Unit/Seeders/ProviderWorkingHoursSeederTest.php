<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ProviderWorkingHoursSeeder;
use PHPUnit\Framework\Attributes\Test;

class ProviderWorkingHoursSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_provider_working_hours_correctly()
    {
        $databasePrefix = "Boo_";

        // First, create users and seed some providers
        User::factory()->create();
        Service::factory()->create();
        $this->seed(\Database\Seeders\ProvidersSeeder::class);

        // Run the working hours seeder
        $this->seed(ProviderWorkingHoursSeeder::class);

        $providers = Provider::all();

        foreach ($providers as $provider) {
            // Weekdays: Monday (1) to Friday (5)
            for ($day = 1; $day <= 5; $day++) {
                $this->assertDatabaseHas($databasePrefix . 'ProviderWorkingHours', [
                    'Provider_ID' => $provider->Provider_ID,
                    'PWH_DayOfWeek' => $day
                ]);
            }

            // Saturday (6)
            $this->assertDatabaseHas($databasePrefix . 'ProviderWorkingHours', [
                'Provider_ID' => $provider->Provider_ID,
                'PWH_DayOfWeek' => 6
            ]);
        }

        // Optional: Assert total number of working hours inserted
        $expectedCount = ($providers->count() * 6); // 5 weekdays + 1 Saturday
        $this->assertDatabaseCount($databasePrefix . 'ProviderWorkingHours', $expectedCount);
    }
}
