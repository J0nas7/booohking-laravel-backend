<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use Illuminate\Database\Seeder;

class ProviderWorkingHoursSeeder extends Seeder
{
    public function run(): void
    {
        $providers = Provider::all();

        if ($providers->isEmpty()) {
            $providers = Provider::factory()->count(5)->create();
        }

        foreach ($providers as $provider) {
            // Monday to Friday
            for ($day = 1; $day <= 5; $day++) {
                ProviderWorkingHour::factory()
                    ->forProvider($provider)
                    ->weekday()
                    ->create([
                        'PWH_DayOfWeek' => $day,
                    ]);
            }

            // Saturday
            ProviderWorkingHour::factory()
                ->forProvider($provider)
                ->saturday()
                ->create();
        }
    }
}
