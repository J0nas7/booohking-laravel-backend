<?php

use App\Helpers\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $databasePrefix = "Boo_";
        Schema::create($databasePrefix . 'Users', function (Blueprint $table) {
            $prefix = 'User_';

            $table->bigIncrements($prefix . 'ID');
            $table->string($prefix . 'Name', 255);
            $table->string($prefix . 'Email', 255)->unique();
            $table->string('email', 255)->unique();
            $table->string($prefix . 'Password', 255);
            $table->string($prefix . 'Remember_Token', 100)->nullable();
            $table->string($prefix . 'Email_Verification_Token')->nullable();
            $table->dateTime($prefix . 'Email_VerifiedAt', 255)->nullable();

            $table->enum($prefix . 'Role', ['ROLE_ADMIN', 'ROLE_USER'])->default('ROLE_USER');

            MigrationHelper::addDateTimeFields($table, $prefix);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $databasePrefix = "Boo_";
        Schema::dropIfExists($databasePrefix . 'Users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
