<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin role
        $user = [
            'User_Name' => 'Jonas Admin',
            'User_Email' => 'jonas-adm@booohking.com',
            'User_Role' => 'ROLE_ADMIN',
            'User_Password' => Hash::make('abc123def'),
            'User_Email_VerifiedAt' => now()
        ];

        User::create($user);

        // User role
        $user = [
            'User_Name' => 'Jonas User',
            'User_Email' => 'jonas-usr@booohking.com',
            'User_Role' => 'ROLE_USER',
            'User_Password' => Hash::make('abc123def'),
            'User_Email_VerifiedAt' => now()
        ];

        User::create($user);

        // 5 other regular users
        User::factory()->count(5)->create();
    }
}
