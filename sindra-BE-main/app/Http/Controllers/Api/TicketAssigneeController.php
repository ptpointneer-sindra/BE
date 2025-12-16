<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketNotificationMail;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;

class TicketAssigneeController extends Controller
{
    public function index($ticketId)
    {
        $assignees = TicketAssignee::with('user')->where('ticket_id', $ticketId)->get();
        return response()->json($assignees);
    }

    public function store(Request $request, $ticketId)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $ticket = Ticket::with('reporter')->findOrFail($ticketId);

        $addedUsers = [];


        foreach ($request->user_ids as $userId) {
            $assignee = TicketAssignee::firstOrCreate([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
            ]);

            $user = User::find($userId);

            if ($user && $user->email) {
                // 📧 Kirim notifikasi ke assignee baru
                Mail::to($user->email)->send(
                    new TicketNotificationMail(
                        $ticket,
                        'Anda Telah Ditugaskan pada Tiket Baru',
                        "Halo {$user->name},\n\nAnda telah ditugaskan untuk menangani tiket berikut:\n\n**{$ticket->title}**\n\nMohon segera ditindaklanjuti."
                    )
                );
            }

            $addedUsers[] = $assignee;
        }

        

        return response()->json(['message' => 'Assignees added successfully']);
    }

    public function destroy($ticketId, $userId)
    {
        $assignee = TicketAssignee::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $assignee->delete();

        $user = User::find($userId);

        return response()->json(['message' => 'Assignee removed successfully']);
    }
}
