<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $subjectText;
    public $messageText;

    /**
     * Create a new message instance.
     */
    public function __construct($ticket, $subjectText, $messageText)
    {
        $this->ticket = $ticket;
        $this->subjectText = $subjectText;
        $this->messageText = $messageText;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subjectText)
                    ->markdown('emails.tickets.notification')
                    ->with([
                        'ticket' => $this->ticket,
                        'messageText' => $this->messageText,
                    ]);
    }
}
