<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketLog;
use Illuminate\Http\Request;

class TicketLogController extends Controller
{
    public function index($ticketId)
    {
        $logs = TicketLog::with('user')
            ->where('ticket_id', $ticketId)
            ->orderBy('time_at', 'desc')
            ->get();

        return response()->json([
            'ticket_id' => $ticketId,
            'data' => $logs,
        ]);
    }
}
