<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderWorkingHour>
 */
class ProviderWorkingHourFactory extends Factory
{
    protected $model = ProviderWorkingHour::class;

    public function definition(): array
    {
        return [
            'Provider_ID' => Provider::factory(), // Creates a provider if not provided
            'PWH_DayOfWeek' => $this->faker->numberBetween(0, 6),
            'PWH_StartTime' => sprintf('%02d:00:00', $this->faker->numberBetween(8, 11)),
            'PWH_EndTime' => sprintf('%02d:00:00', $this->faker->numberBetween(16, 20)),
        ];
    }

    /**
     * Assign this working hour to an existing provider
     */
    public function forProvider(Provider $provider): static
    {
        return $this->state(fn() => [
            'Provider_ID' => $provider->Provider_ID,
        ]);
    }

    /**
     * Predefined Mon-Fri working hours
     */
    public function weekday(): static
    {
        return $this->state(fn() => [
            'PWH_DayOfWeek' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Predefined Saturday working hours
     */
    public function saturday(): static
    {
        return $this->state(fn() => [
            'PWH_DayOfWeek' => 6,
            'PWH_StartTime' => sprintf('%02d:00:00', $this->faker->numberBetween(9, 11)),
            'PWH_EndTime' => sprintf('%02d:00:00', $this->faker->numberBetween(13, 15)),
        ]);
    }
}
