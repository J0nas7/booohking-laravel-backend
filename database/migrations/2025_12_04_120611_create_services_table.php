<?php

use App\Helpers\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $prefix = 'Service_';

            $table->bigIncrements($prefix . 'ID');
            $table->unsignedBigInteger('User_ID');
            $table->string($prefix . 'Name', 255);
            $table->integer($prefix . 'DurationMinutes');
            $table->text($prefix . 'Description')->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('User_ID')
                ->references('User_ID')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
