<?php

namespace App\Actions\SendResetToken;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;

class SendResetEmail
{
    public function __construct(protected Mailer $mail) {}

    public function execute(User $user, string $token): array
    {
        try {
            $this->mail->to($user->User_Email)
                ->send(new ForgotPasswordMail($user, $token));

            return [
                'status' => 'Email sent successfully.',
                'token'  => '',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());

            return [
                'status' => 'Failed to send email: ' . $e->getMessage(),
                'token'  => $token,
            ];
        }
    }
}
