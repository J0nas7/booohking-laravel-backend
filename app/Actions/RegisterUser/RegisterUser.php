<?php

namespace App\Actions\RegisterUser;

use App\Helpers\ServiceResponse;

class RegisterUser
{
    public function __construct(
        protected ValidateRegistration $validator,
        protected CreateUser $creator,
        protected SendVerificationEmail $mailer
    ) {}

    public function execute(array $data): ServiceResponse
    {
        $validation = $this->validator->execute($data);

        if ($validation !== true) {
            return new ServiceResponse(
                errors: $validation->toArray(),
                status: 400
            );
        }

        $user = $this->creator->execute($data);

        $emailResult = $this->mailer->execute($user);

        return new ServiceResponse(
            data: [
                'email_status' => $emailResult['status'],
                'user'         => $user,
                'token'        => $emailResult['token'],
            ],
            message: 'User registered successfully',
            status: 201
        );
    }
}
