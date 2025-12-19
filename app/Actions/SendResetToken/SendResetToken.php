<?php

namespace App\Actions\SendResetToken;

use App\Helpers\ServiceResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendResetToken
{
    public function execute(array $validated): ServiceResponse
    {
        try {
            $response = Password::sendResetLink([
                'email' => $validated['email'],
            ]);
        } catch (TransportExceptionInterface $e) {
            // Log full details for devs
            Log::error('Password reset email failed', [
                'email' => $validated['email'],
                'exception' => $e->getMessage(),
            ]);

            // Safe, frontend-friendly message
            return new ServiceResponse(
                error: 'We could not send the password reset email at this time. Please try again later.',
                status: 503
            );
        }

        if ($response === Password::RESET_LINK_SENT) {
            return new ServiceResponse(
                message: 'If an account with that email exists, a password reset link has been sent.',
                status: 200
            );
        }

        return match ($response) {
            Password::INVALID_USER => new ServiceResponse(
                error: 'If an account with that email exists, a password reset link has been sent.',
                status: 200
            ),

            Password::RESET_THROTTLED => new ServiceResponse(
                error: 'Please wait before requesting another password reset email.',
                status: 429
            ),

            default => new ServiceResponse(
                error: 'Unable to send password reset email. Please try again later.',
                status: 400
            ),
        };
    }
}
