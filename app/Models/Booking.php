<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends BaseModel
{
    use SoftDeletes;

    const MODEL_NAME = "Booking";
    const DELETED_AT = self::MODEL_NAME . '_DeletedAt';

    protected static function getModelPrefix(): string
    {
        return self::MODEL_NAME;
    }

    protected function getFillableFields(): array
    {
        return [
            'User_ID',
            'Provider_ID',
            'Service_ID',
            $this->fieldPrefix . 'StartAt',
            $this->fieldPrefix . 'EndAt',
            $this->fieldPrefix . 'Status',
            $this->fieldPrefix . 'CancelledAt',
        ];
    }

    protected function getCastsFields(): array
    {
        return [
            $this->fieldPrefix . 'StartAt' => 'datetime',
            $this->fieldPrefix . 'EndAt' => 'datetime',
            $this->fieldPrefix . 'CancelledAt' => 'datetime',
            $this->fieldPrefix . 'CreatedAt' => 'datetime',
            $this->fieldPrefix . 'UpdatedAt' => 'datetime',
            $this->fieldPrefix . 'DeletedAt' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID', 'User_ID');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'Provider_ID', 'Provider_ID');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'Service_ID', 'Service_ID');
    }
}
