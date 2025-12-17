<?php

namespace App\Helpers;

class ServiceResponse
{
    public function __construct(
        public mixed $data = null,
        public ?string $message = '',
        public ?array $errors = null,
        public ?string $error = '',
        public ?int $status = 200
    ) {}
}
