<?php

use App\Helpers\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $databasePrefix = "Boo_";
        Schema::create($databasePrefix . 'Bookings', function (Blueprint $table) use ($databasePrefix) {
            $prefix = 'Booking_';

            $table->bigIncrements($prefix . 'ID');
            $table->unsignedBigInteger('User_ID');
            $table->unsignedBigInteger('Provider_ID');
            $table->unsignedBigInteger('Service_ID');
            $table->dateTime($prefix . 'StartAt');
            $table->dateTime($prefix . 'EndAt');
            $table->enum($prefix . 'Status', ['booked', 'cancelled'])->default('booked');
            $table->dateTime($prefix . 'CancelledAt')->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('User_ID')
                ->references('User_ID')
                ->on($databasePrefix . 'Users')
                ->onDelete('cascade');

            $table->foreign('Provider_ID')
                ->references('Provider_ID')
                ->on($databasePrefix . 'Providers')
                ->onDelete('cascade');

            $table->foreign('Service_ID')
                ->references('Service_ID')
                ->on($databasePrefix . 'Services')
                ->onDelete('cascade');

            $table->unique(['Provider_ID', $prefix . 'StartAt']); // Prevent double-booking
        });
    }

    public function down(): void
    {
        $databasePrefix = "Boo_";
        Schema::dropIfExists($databasePrefix . 'Bookings');
    }
};
