<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create service owner
        $user = User::firstOrCreate(
            ['User_Email' => 'jonas-adm@booohking.com'],
            [
                'User_Name' => 'Admin',
                'User_Password' => bcrypt('password'),
            ]
        );

        // Create 6 services owned by that user
        Service::factory()
            ->count(6)
            ->forUser($user)
            ->create();
    }
}
