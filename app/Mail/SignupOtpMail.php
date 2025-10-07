<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignupOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $email;

    public function __construct(string $otp, string $email)
    {
        $this->otp = $otp;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Verification OTP - GoodDeeds',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signup-otp',
            with: [
                'otp' => $this->otp,
                'email' => $this->email,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}