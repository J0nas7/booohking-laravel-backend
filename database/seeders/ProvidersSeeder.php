<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Get all services
        $services = Service::all();

        foreach ($services as $service) {
            // Create 2-3 random providers for each service
            $numProviders = rand(2, 3);

            for ($i = 0; $i < $numProviders; $i++) {
                $provider = Provider::create([
                    'Provider_Name' => $faker->name(),
                    'Provider_Timezone' => 'UTC',
                    'Service_ID' => $service->Service_ID,
                ]);

                // Attach service if many-to-many
                if (method_exists($provider, 'services')) {
                    $provider->services()->attach($service->Service_ID);
                }
            }
        }
    }
}
