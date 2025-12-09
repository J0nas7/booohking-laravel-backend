<?php

namespace Tests\Unit\Seeders;

use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ProvidersSeeder;
use PHPUnit\Framework\Attributes\Test;

class ProvidersSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_providers_table_correctly()
    {
        $databasePrefix = "Boo_";

        // Run the seeder
        User::factory()->create();
        Service::factory()->create();
        $this->seed(ProvidersSeeder::class);

        // Assert the expected number of providers were inserted
        $providersCount = Provider::count();
        $this->assertTrue(
            $providersCount === 2 || $providersCount === 3,
            "Expected 2 or 3 providers, got {$providersCount}"
        );

        // Assert specific records exist
        $this->assertDatabaseHas($databasePrefix . 'Providers', [
            'Provider_Timezone' => 'UTC',
            'Service_ID' => '1',
        ]);
    }
}
