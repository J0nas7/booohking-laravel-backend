<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        // Create one service owner
        $user = User::factory()->create();

        // Create 6 services owned by that user
        Service::factory()
            ->count(6)
            ->forUser($user)
            ->create();
    }
}
