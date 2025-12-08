<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderWorkingHour>
 */
class ProviderWorkingHourFactory extends Factory
{
    protected $model = ProviderWorkingHour::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Provider_ID' => Provider::factory(),   // auto-creates provider
            'PWH_DayOfWeek' => $this->faker->numberBetween(0, 6),
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '17:00',
        ];
    }
}
