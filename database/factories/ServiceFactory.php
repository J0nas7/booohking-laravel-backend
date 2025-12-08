<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Service_Name' => $this->faker->words(2, true),
            'User_ID' => 1,
            'Service_Description' => $this->faker->sentence(),
            'Service_DurationMinutes' => $this->faker->randomElement([30, 45, 60, 90]),
        ];
    }
}
