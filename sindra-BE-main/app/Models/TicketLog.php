<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    protected $table = 'ticket_logs';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'name',
        'desc',
        'time_at',
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

    public static function addLog($ticketId, $userId, $name, $desc = null)
    {
        // Ambil log terakhir yang punya ticket_id dan user_id yang sama
        $lastLog = self::where('ticket_id', $ticketId)
            ->orderBy('time_at', 'desc')
            ->first();

        // Default time sekarang
        $time = Carbon::now();

        // Jika ada log sebelumnya, tambahkan +1 detik
        if ($lastLog) {
            $time = Carbon::parse($lastLog->time_at)->addSecond();
        }

        return self::create([
            'ticket_id' => $ticketId,
            'user_id'   => $userId,
            'name'      => $name,
            'desc'      => $desc,
            'time_at'   => $time,
        ]);
    }

}
