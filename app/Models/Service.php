<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends BaseModel
{
    use SoftDeletes;

    const MODEL_NAME = "Service";
    const DELETED_AT = self::MODEL_NAME . '_DeletedAt';

    protected static function getModelPrefix(): string
    {
        return self::MODEL_NAME;
    }

    protected function getFillableFields(): array
    {
        return [
            $this->fieldPrefix . 'Name',
            $this->fieldPrefix . 'DurationMinutes',
            $this->fieldPrefix . 'Description',
            'User_ID'
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

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'Service_ID', $this->primaryKey);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class, 'Service_ID', $this->primaryKey);
    }
}
