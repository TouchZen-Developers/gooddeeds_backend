<?php

namespace App\Mail;

use App\Models\Beneficiary;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Beneficiary $beneficiary;
    public string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Beneficiary $beneficiary, string $reason = '')
    {
        $this->beneficiary = $beneficiary;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your GoodDeeds Application',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.beneficiary-rejected',
            with: [
                'beneficiary' => $this->beneficiary,
                'userName' => $this->beneficiary->user->first_name ?? $this->beneficiary->user->name,
                'reason' => $this->reason,
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
