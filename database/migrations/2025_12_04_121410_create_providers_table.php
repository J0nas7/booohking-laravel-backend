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
        Schema::create($databasePrefix . 'Providers', function (Blueprint $table) use ($databasePrefix) {
            $prefix = 'Provider_';

            $table->bigIncrements($prefix . 'ID');
            $table->unsignedBigInteger('Service_ID');
            $table->string($prefix . 'Name', 255);
            $table->string($prefix . 'Timezone', 50)->default('UTC');

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('Service_ID')
                ->references('Service_ID')
                ->on($databasePrefix . 'Services')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $databasePrefix = "Boo_";
        Schema::dropIfExists($databasePrefix . 'Providers');
    }
};
