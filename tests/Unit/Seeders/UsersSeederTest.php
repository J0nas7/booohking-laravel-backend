<?php

namespace Tests\Unit\Seeders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\UsersSeeder;
use PHPUnit\Framework\Attributes\Test;

class UsersSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_users_table_correctly()
    {
        $databasePrefix = "Boo_";

        // Run the seeder
        $this->seed(UsersSeeder::class);

        // Total of 7 users: 1 admin + 1 user + 5 factory users
        $this->assertDatabaseCount($databasePrefix . 'Users', 7);

        // Assert admin role user exists
        $this->assertDatabaseHas($databasePrefix . 'Users', [
            'User_Name' => 'Jonas Admin',
            'User_Email' => 'jonas-adm@booohking.com',
        ]);

        // Assert user role user exists
        $this->assertDatabaseHas($databasePrefix . 'Users', [
            'User_Name' => 'Jonas User',
            'User_Email' => 'jonas-usr@booohking.com',
        ]);

        // Assert there are users created by factory (not the manual ones)
        $factoryUsersCount = User::whereNotIn('User_Email', [
            'jonas-adm@booohking.com',
            'jonas-usr@booohking.com'
        ])->count();
        $this->assertEquals(5, $factoryUsersCount);
    }
}
