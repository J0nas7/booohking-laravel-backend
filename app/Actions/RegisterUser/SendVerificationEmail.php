<?php

namespace App\Actions\RegisterUser;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;

class SendVerificationEmail
{
    public function __construct(protected Mailer $mail) {}

    public function execute(User $user): array
    {
        $token = $user->User_Email_Verification_Token;

        try {
            $this->mail->to($user->User_Email)
                ->send(new WelcomeEmail($user, $token));

            return [
                'status' => 'Email sent successfully.',
                'token'  => '',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send registration email: ' . $e->getMessage());

            return [
                'status' => 'Failed to send email: ' . $e->getMessage(),
                'token'  => $token,
            ];
        }
    }
}
