<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $expiresInMinutes;
    public $user; // tambahin user

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $expiresInMinutes = 10, $user = null)
    {
        $this->otp = $otp;
        $this->expiresInMinutes = $expiresInMinutes;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Lupa Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'password.otp',
            with: [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
                'user' => $this->user,
            ],
        );
    }

    /**
     * Attachments.
     */
    public function attachments(): array
    {
        return [];
    }
}