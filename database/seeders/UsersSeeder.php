<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create one admin
        User::factory()->admin()->create([
            'name' => 'Jonas Admin',
            'email' => 'jonas-adm@booohking.com',
            'password' => bcrypt('abc123def'), // optional override
        ]);

        // Create one regular user
        User::factory()->create([
            'name' => 'Jonas User',
            'email' => 'jonas-usr@booohking.com',
            'password' => bcrypt('abc123def'),
        ]);

        // Create 5 additional random users
        User::factory()->count(5)->create();
    }
}
