<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UsersTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $table = 'users';

    #[Test]
    public function it_creates_users_table_with_correct_schema()
    {
        // Run the migration
        $this->artisan('migrate');

        // Assert that the table exists
        $this->assertTrue(Schema::hasTable($this->table));

        // Assert expected columns exist
        $expectedColumns = [
            'User_ID',
            'User_Name',
            'User_Email',
            'User_Password',
            'email_verification_token',
            'email_verified_at',
            'User_Role',
            'User_CreatedAt',
            'User_UpdatedAt',
            'User_DeletedAt',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn($this->table, $column), "Column {$column} does not exist");
        }

        // SQLite: check column types
        $columns = collect(DB::select("PRAGMA table_info({$this->table})"));

        $userId = $columns->where('name', 'User_ID')->first();
        $this->assertEquals('integer', strtolower($userId->type));

        $userName = $columns->where('name', 'User_Name')->first();
        $this->assertEquals('varchar', strtolower($userName->type));

        $userEmail = $columns->where('name', 'User_Email')->first();
        $this->assertEquals('varchar', strtolower($userEmail->type));

        $userPassword = $columns->where('name', 'User_Password')->first();
        $this->assertEquals('varchar', strtolower($userPassword->type));

        $emailVerificationToken = $columns->where('name', 'email_verification_token')->first();
        $this->assertEquals('varchar', strtolower($emailVerificationToken->type));

        $emailVerifiedAt = $columns->where('name', 'email_verified_at')->first();
        $this->assertEquals('datetime', strtolower($emailVerifiedAt->type));

        $role = $columns->where('name', 'User_Role')->first();
        $this->assertEquals('varchar', strtolower($role->type));
    }

    #[Test]
    public function it_drops_users_table_on_rollback()
    {
        // Run the migration
        $this->artisan('migrate');

        // Rollback the migration
        $this->artisan('migrate:rollback');

        // Assert table does not exist
        $this->assertFalse(Schema::hasTable($this->table));
    }
}
