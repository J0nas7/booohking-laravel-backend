<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'User_Name'             => $this->faker->firstName,
            'email'                 => $this->faker->unique()->safeEmail,
            'User_Email'            => $this->faker->unique()->safeEmail,
            'User_Password'         => bcrypt('password'), // default password
            'email_verified_at'     => now(),
            'User_CreatedAt'        => now(),
            'User_UpdatedAt'        => now(),
            'User_DeletedAt'        => null,
            'role'             => 'ROLE_USER', // default role
        ];
    }

    /**
     * Mark user as admin.
     */
    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'ROLE_ADMIN',
        ]);
    }

    /**
     * Indicate that the email should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn() => [
            'email_verified_at' => null,
        ]);
    }
}
