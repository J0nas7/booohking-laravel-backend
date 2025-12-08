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
        Schema::create($databasePrefix . 'ProviderWorkingHours', function (Blueprint $table) use ($databasePrefix) {
            $prefix = 'PWH_';

            $table->bigIncrements($prefix . 'ID');
            $table->unsignedBigInteger('Provider_ID');
            $table->tinyInteger($prefix . 'DayOfWeek'); // 0=Sunday ... 6=Saturday
            $table->time($prefix . 'StartTime');
            $table->time($prefix . 'EndTime');

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('Provider_ID')
                ->references('Provider_ID')
                ->on($databasePrefix . 'Providers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $databasePrefix = "Boo_";
        Schema::dropIfExists($databasePrefix . 'ProviderWorkingHours');
    }
};
