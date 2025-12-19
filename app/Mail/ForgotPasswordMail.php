<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $token;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->to($user->email);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Password Reset Token')
            ->view('emails.forgot_password')
            ->with([
                'user' => $this->user,
                'token' => $this->token,
            ]);
    }
}
