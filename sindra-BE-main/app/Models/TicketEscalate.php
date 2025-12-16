<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEscalate extends Model
{
    protected $fillable = [
        'ticket_id',
        'description',
        'destination',
        'status',
    ];

    /**
     * Relasi ke Ticket
     * ticket_escalates.ticket_id → tickets.id
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relasi untuk mengetahui user yang membuat tiket (reporter)
     * tickets.reporter_id → users.id
     */
    public function reporter()
    {
        return $this->hasOneThrough(
            User::class,     // Target model
            Ticket::class,   // Model perantara
            'id',            // Foreign key di Ticket
            'id',            // Foreign key di User
            'ticket_id',     // FK di TicketEscalate (mulai dari sini)
            'reporter_id'    // FK di Ticket (menuju User)
        );
    }
}
