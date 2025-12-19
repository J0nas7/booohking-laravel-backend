<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'Service_Name' => $this->faker->jobTitle(),
            'Service_Description' => $this->faker->sentence(),
            'Service_DurationMinutes' => $this->faker->randomElement([30, 45, 60, 90]),

            // Relationship-driven FK
            'User_ID' => User::factory(),
        ];
    }

    /**
     * Assign service to an existing user (owner)
     */
    public function forUser(User $user): static
    {
        return $this->state(fn() => [
            'User_ID' => $user->id,
        ]);
    }
}
