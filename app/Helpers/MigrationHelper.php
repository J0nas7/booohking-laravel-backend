<?php

namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;

class MigrationHelper
{
    /**
     * Add common dateTime fields to a table.
     *
     * @param Blueprint $table
     * @param string $prefix
     * @param boolean $softDeletes
     */
    public static function addDateTimeFields(Blueprint $table, string $prefix, bool $softDeletes = true)
    {
        $table->dateTime($prefix . 'CreatedAt')->nullable();
        $table->dateTime($prefix . 'UpdatedAt')->nullable();

        if ($softDeletes) {
            $table->softDeletes($prefix . 'DeletedAt');
        } else {
            $table->dateTime($prefix . 'DeletedAt')->nullable();
        }
    }
}
