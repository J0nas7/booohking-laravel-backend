<?php

namespace App\Actions\RegisterUser;

use App\Helpers\ServiceResponse;

class RegisterUser
{
    public function __construct(
        protected CreateUser $creator,
        protected SendVerificationEmail $mailer
    ) {}

    public function execute(array $validated): ServiceResponse
    {
        $user = $this->creator->execute($validated);

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
