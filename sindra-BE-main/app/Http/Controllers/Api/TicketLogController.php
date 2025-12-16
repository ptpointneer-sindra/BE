<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required',
            'name'      => 'required',
            'desc'      => 'nullable',
        ]);

        $userId = Auth::id();

        $log = TicketLog::addLog(
            $request->ticket_id,
            $userId,
            $request->name,
            $request->desc
        );

        return response()->json([
            'success' => true,
            'message' => 'Log berhasil ditambahkan',
            'data'    => $log
        ]);
    }
}
