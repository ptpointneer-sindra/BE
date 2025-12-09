<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReopen extends Model
{
    protected $table = 'ticket_reopens';

    protected $fillable = [
        'ticket_id',
        'reason',
        'detail',
        'attachment',
        'status',
    ];

    // Relasi ke Ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
