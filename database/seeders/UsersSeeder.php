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
            'User_Name' => 'Jonas Admin',
            'email' => 'jonas-adm@booohking.com',
            'User_Email' => 'jonas-adm@booohking.com',
            'User_Password' => bcrypt('abc123def'), // optional override
        ]);

        // Create one regular user
        User::factory()->create([
            'User_Name' => 'Jonas User',
            'email' => 'jonas-usr@booohking.com',
            'User_Email' => 'jonas-usr@booohking.com',
            'User_Password' => bcrypt('abc123def'),
        ]);

        // Create 5 additional random users
        User::factory()->count(5)->create();
    }
}
