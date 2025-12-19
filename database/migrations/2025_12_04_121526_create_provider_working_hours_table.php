<?php

use App\Helpers\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_working_hours', function (Blueprint $table) {
            $prefix = 'PWH_';

            $table->bigIncrements($prefix . 'ID');
            $table->unsignedBigInteger('Provider_ID');
            $table->tinyInteger($prefix . 'DayOfWeek'); // 0=Sunday ... 6=Saturday
            $table->time($prefix . 'StartTime');
            $table->time($prefix . 'EndTime');

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('Provider_ID')
                ->references('Provider_ID')
                ->on('providers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_working_hours');
    }
};
