<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProviderWorkingHoursTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $table = 'Boo_ProviderWorkingHours';

    #[Test]
    public function it_creates_provider_working_hours_table_with_correct_schema()
    {
        // Run the migration
        $this->artisan('migrate');

        // Assert table exists
        $this->assertTrue(Schema::hasTable($this->table));

        // Expected columns
        $expectedColumns = [
            'PWH_ID',
            'Provider_ID',
            'PWH_DayOfWeek',
            'PWH_StartTime',
            'PWH_EndTime',
            'PWH_CreatedAt',
            'PWH_UpdatedAt',
            'PWH_DeletedAt',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn($this->table, $column), "Column {$column} does not exist");
        }

        // SQLite column types
        $columns = collect(DB::select("PRAGMA table_info({$this->table})"));

        $pwhId = $columns->where('name', 'PWH_ID')->first();
        $this->assertEquals('integer', strtolower($pwhId->type));

        $providerId = $columns->where('name', 'Provider_ID')->first();
        $this->assertEquals('integer', strtolower($providerId->type));

        $dayOfWeek = $columns->where('name', 'PWH_DayOfWeek')->first();
        $this->assertEquals('integer', strtolower($dayOfWeek->type));

        $startTime = $columns->where('name', 'PWH_StartTime')->first();
        $this->assertEquals('time', strtolower($startTime->type));

        $endTime = $columns->where('name', 'PWH_EndTime')->first();
        $this->assertEquals('time', strtolower($endTime->type));
    }

    #[Test]
    public function it_drops_provider_working_hours_table_on_rollback()
    {
        // Run the migration
        $this->artisan('migrate');

        // Rollback the migration
        $this->artisan('migrate:rollback');

        // Assert table does not exist
        $this->assertFalse(Schema::hasTable($this->table));
    }
}
