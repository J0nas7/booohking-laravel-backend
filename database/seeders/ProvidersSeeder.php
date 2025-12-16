<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure services exist
        $services = Service::all();

        if ($services->isEmpty()) {
            $services = Service::factory()->count(5)->create();
        }

        foreach ($services as $service) {
            // Create 2-3 providers for each service
            $numProviders = rand(2, 3);

            Provider::factory()
                ->count($numProviders)
                ->forService($service) // Uses ProviderFactory::forService()
                ->create();
        }
    }
}
