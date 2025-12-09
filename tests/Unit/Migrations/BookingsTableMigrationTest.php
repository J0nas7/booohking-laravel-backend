<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BookingsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $table = 'Boo_Bookings';

    #[Test]
    public function it_creates_bookings_table_with_correct_schema()
    {
        // Run the migration
        $this->artisan('migrate');

        // Assert table exists
        $this->assertTrue(Schema::hasTable($this->table));

        // Expected columns
        $expectedColumns = [
            'Booking_ID',
            'User_ID',
            'Provider_ID',
            'Service_ID',
            'Booking_StartAt',
            'Booking_EndAt',
            'Booking_Status',
            'Booking_CancelledAt',
            'Booking_CreatedAt',
            'Booking_UpdatedAt',
            'Booking_DeletedAt',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn($this->table, $column), "Column {$column} does not exist");
        }

        // SQLite column types
        $columns = collect(DB::select("PRAGMA table_info({$this->table})"));

        $bookingId = $columns->where('name', 'Booking_ID')->first();
        $this->assertEquals('integer', strtolower($bookingId->type));

        $userId = $columns->where('name', 'User_ID')->first();
        $this->assertEquals('integer', strtolower($userId->type));

        $providerId = $columns->where('name', 'Provider_ID')->first();
        $this->assertEquals('integer', strtolower($providerId->type));

        $serviceId = $columns->where('name', 'Service_ID')->first();
        $this->assertEquals('integer', strtolower($serviceId->type));

        $startAt = $columns->where('name', 'Booking_StartAt')->first();
        $this->assertEquals('datetime', strtolower($startAt->type));

        $endAt = $columns->where('name', 'Booking_EndAt')->first();
        $this->assertEquals('datetime', strtolower($endAt->type));

        $status = $columns->where('name', 'Booking_Status')->first();

        if (DB::getDriverName() === 'sqlite') {
            // SQLite maps enum to varchar
            $this->assertEquals('varchar', strtolower($status->type));
        } else {
            $this->assertStringContainsString('enum', strtolower($status->type));
        }
    }

    #[Test]
    public function it_drops_bookings_table_on_rollback()
    {
        // Run the migration
        $this->artisan('migrate');

        // Rollback the migration
        $this->artisan('migrate:rollback');

        // Assert table does not exist
        $this->assertFalse(Schema::hasTable($this->table));
    }
}
