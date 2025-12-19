<?php

namespace App\Actions\SendResetToken;

use App\Helpers\ServiceResponse;
use App\Models\User;

class SendResetToken
{
    public function __construct(
        protected GenerateResetToken $tokenGenerator,
        protected SendResetEmail $emailSender
    ) {}

    public function execute(array $validated): ServiceResponse
    {
        $user = User::where('User_Email', $validated['User_Email'])->first();

        if (!$user) {
            return new ServiceResponse(
                errors: ['User_Email' => 'User not found.'],
                status: 404
            );
        }

        $token = $this->tokenGenerator->execute($user);
        $emailResult = $this->emailSender->execute($user, $token);

        return new ServiceResponse(
            data: [
                'email_status' => $emailResult['status'],
                'user' => $user,
                'token' => $emailResult['token'],
            ],
            message: 'Password reset token sent.',
        );
    }
}
