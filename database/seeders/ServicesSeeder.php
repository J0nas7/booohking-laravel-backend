<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create a user
        User::factory()->create();

        // Generate 6 random services
        for ($i = 0; $i < 6; $i++) {
            Service::create([
                'Service_Name' => $faker->jobTitle(), // e.g., "Senior Hair Stylist"
                'Service_DurationMinutes' => 30, //$faker->numberBetween(15, 120),
                'User_ID' => 1, // assign to the created user
            ]);
        }
    }
}
