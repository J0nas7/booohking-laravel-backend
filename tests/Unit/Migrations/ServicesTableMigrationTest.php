<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServicesTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $table = 'Boo_Services';

    #[Test]
    public function it_creates_services_table_with_correct_schema()
    {
        // Run the migration
        $this->artisan('migrate');

        // Assert that the table exists
        $this->assertTrue(Schema::hasTable($this->table));

        // Assert that the table has the expected columns
        $expectedColumns = [
            'Service_ID',
            'Service_Name',
            'Service_DurationMinutes',
            'Service_Description',
            'Service_CreatedAt',
            'Service_UpdatedAt',
            'Service_DeletedAt',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn($this->table, $column), "Column {$column} does not exist");
        }

        // SQLite: check column types
        $columns = collect(DB::select("PRAGMA table_info({$this->table})"));

        $serviceId = $columns->where('name', 'Service_ID')->first();
        $this->assertEquals('integer', strtolower($serviceId->type));

        $serviceName = $columns->where('name', 'Service_Name')->first();
        $this->assertEquals('varchar', strtolower($serviceName->type));

        $duration = $columns->where('name', 'Service_DurationMinutes')->first();
        $this->assertEquals('integer', strtolower($duration->type));

        $description = $columns->where('name', 'Service_Description')->first();
        $this->assertEquals('text', strtolower($description->type));
    }

    #[Test]
    public function it_drops_services_table_on_rollback()
    {
        // Run the migration
        $this->artisan('migrate');

        // Rollback the migration
        $this->artisan('migrate:rollback');

        // Assert that the table does not exist
        $this->assertFalse(Schema::hasTable($this->table));
    }
}
