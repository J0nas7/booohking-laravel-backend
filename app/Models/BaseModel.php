<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    use HasFactory;

    // Abstract methods to be implemented by child classes
    abstract protected static function getModelPrefix(): string;
    abstract protected function getFillableFields(): array;
    abstract protected function getCastsFields(): array;

    // Table name is constructed as plural model name
    protected $table;

    // Primary key is constructed as model prefix + 'ID'
    protected $primaryKey;

    // Property for the field prefix
    protected $fieldPrefix;

    protected $dates = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // $this->table = Str::plural(class_basename(static::class));
        $this->primaryKey = static::getModelPrefix() . '_ID';
        $this->fieldPrefix = static::getModelPrefix() . '_';

        $this->dates = [
            $this->fieldPrefix . 'CreatedAt',
            $this->fieldPrefix . 'UpdatedAt',
            $this->fieldPrefix . 'DeletedAt'
        ];

        // Set $fillable and $casts in the constructor
        $this->fillable = $this->getFillableFields();
        $this->casts = $this->getCastsFields();
    }

    public function getTableName(): string
    {
        return (new static())->getTable();
    }

    public function getFieldName(string $field): string
    {
        return $this->fieldPrefix . $field;
    }

    public function getCreatedAtColumn()
    {
        return $this->fieldPrefix . 'CreatedAt';
    }

    public function getUpdatedAtColumn()
    {
        return $this->fieldPrefix . 'UpdatedAt';
    }
}
