<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderWorkingHour extends BaseModel
{
    use SoftDeletes;

    const MODEL_NAME = "PWH"; // Short prefix for ProviderWorkingHour
    const DELETED_AT = self::MODEL_NAME . '_DeletedAt';

    protected static function getModelPrefix(): string
    {
        return self::MODEL_NAME;
    }

    protected function getFillableFields(): array
    {
        return [
            'Provider_ID',
            $this->fieldPrefix . 'DayOfWeek',
            $this->fieldPrefix . 'StartTime',
            $this->fieldPrefix . 'EndTime',
        ];
    }

    protected function getCastsFields(): array
    {
        return [
            $this->fieldPrefix . 'StartTime' => 'string', // stored as time string
            $this->fieldPrefix . 'EndTime' => 'string',
            $this->fieldPrefix . 'CreatedAt' => 'datetime',
            $this->fieldPrefix . 'UpdatedAt' => 'datetime',
            $this->fieldPrefix . 'DeletedAt' => 'datetime',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'Provider_ID', 'Provider_ID');
    }
}
