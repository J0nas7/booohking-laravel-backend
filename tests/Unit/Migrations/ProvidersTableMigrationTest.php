<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProvidersTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $table = 'providers';

    #[Test]
    public function it_creates_providers_table_with_correct_schema()
    {
        // Run the migration
        $this->artisan('migrate');

        // Assert table exists
        $this->assertTrue(Schema::hasTable($this->table));

        // Expected columns
        $expectedColumns = [
            'Provider_ID',
            'Provider_Name',
            'Provider_Timezone',
            'Provider_CreatedAt',
            'Provider_UpdatedAt',
            'Provider_DeletedAt',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn($this->table, $column), "Column {$column} does not exist");
        }

        // SQLite column types
        $columns = collect(DB::select("PRAGMA table_info({$this->table})"));

        $providerId = $columns->where('name', 'Provider_ID')->first();
        $this->assertEquals('integer', strtolower($providerId->type));

        $providerName = $columns->where('name', 'Provider_Name')->first();
        $this->assertEquals('varchar', strtolower($providerName->type));

        $providerTimezone = $columns->where('name', 'Provider_Timezone')->first();
        $this->assertEquals('varchar', strtolower($providerTimezone->type));
    }

    #[Test]
    public function it_drops_providers_table_on_rollback()
    {
        // Run the migration
        $this->artisan('migrate');

        // Rollback the migration
        $this->artisan('migrate:rollback');

        // Assert table does not exist
        $this->assertFalse(Schema::hasTable($this->table));
    }
}
