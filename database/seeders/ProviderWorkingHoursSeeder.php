<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProviderWorkingHoursSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $providers = Provider::all();

        foreach ($providers as $provider) {
            // Monday to Friday
            for ($day = 1; $day <= 5; $day++) {
                $startHour = $faker->numberBetween(8, 11); // 08:00 - 11:00
                $endHour = $faker->numberBetween(16, 20);  // 16:00 - 20:00

                ProviderWorkingHour::create([
                    'Provider_ID' => $provider->Provider_ID,
                    'PWH_DayOfWeek' => $day,
                    'PWH_StartTime' => sprintf('%02d:00:00', $startHour),
                    'PWH_EndTime' => sprintf('%02d:00:00', $endHour),
                ]);
            }

            // Saturday
            $startHour = $faker->numberBetween(9, 11);  // 09:00 - 11:00
            $endHour = $faker->numberBetween(13, 15);   // 13:00 - 15:00

            ProviderWorkingHour::create([
                'Provider_ID' => $provider->Provider_ID,
                'PWH_DayOfWeek' => 6,
                'PWH_StartTime' => sprintf('%02d:00:00', $startHour),
                'PWH_EndTime' => sprintf('%02d:00:00', $endHour),
            ]);
        }
    }
}
