<?php

namespace Tests\Unit\Seeders;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ServicesSeeder;
use PHPUnit\Framework\Attributes\Test;

class ServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_services_table_correctly()
    {
        $databasePrefix = "Boo_";

        // Run the seeder
        $this->seed(ServicesSeeder::class);

        // Assert total number of services inserted
        $this->assertDatabaseCount($databasePrefix . 'Services', 6);

        // Assert specific services
        $this->assertDatabaseHas($databasePrefix . 'Services', [
            'User_ID' => 1,
        ]);
    }
}
