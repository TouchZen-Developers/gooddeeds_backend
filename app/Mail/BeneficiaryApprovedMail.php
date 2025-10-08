<?php

namespace App\Mail;

use App\Models\Beneficiary;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Beneficiary $beneficiary;

    /**
     * Create a new message instance.
     */
    public function __construct(Beneficiary $beneficiary)
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Good News! Your GoodDeeds Application Has Been Approved',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.beneficiary-approved',
            with: [
                'beneficiary' => $this->beneficiary,
                'userName' => $this->beneficiary->user->first_name ?? $this->beneficiary->user->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
