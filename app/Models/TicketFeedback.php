<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketFeedback extends Model
{
    protected $table = 'ticket_feedbacks';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'feedback',
        'rating',
    ];

    // Relasi ke Ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
