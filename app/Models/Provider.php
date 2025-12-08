<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends BaseModel
{
    use SoftDeletes;

    const MODEL_NAME = "Provider";
    const DELETED_AT = self::MODEL_NAME . '_DeletedAt';

    protected static function getModelPrefix(): string
    {
        return self::MODEL_NAME;
    }

    protected function getFillableFields(): array
    {
        return [
            $this->fieldPrefix . 'Name',
            $this->fieldPrefix . 'Timezone',
            'Service_ID'
        ];
    }

    protected function getCastsFields(): array
    {
        return [
            $this->fieldPrefix . 'CreatedAt' => 'datetime',
            $this->fieldPrefix . 'UpdatedAt' => 'datetime',
            $this->fieldPrefix . 'DeletedAt' => 'datetime',
        ];
    }

    public function workingHours()
    {
        return $this->hasMany(ProviderWorkingHour::class, 'Provider_ID', $this->primaryKey);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'Provider_ID', $this->primaryKey);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'Service_ID', 'Service_ID');
    }
}
