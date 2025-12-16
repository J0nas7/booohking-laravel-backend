<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'Provider_Name' => $this->faker->company(),
            'Provider_Timezone' => 'UTC',

            // Relationship-driven FK
            'Service_ID' => Service::factory(),
        ];
    }

    /**
     * Attach provider to an existing service instead of creating a new one
     */
    public function forService(Service $service): static
    {
        return $this->state(fn() => [
            'Service_ID' => $service->Service_ID,
        ]);
    }
}
