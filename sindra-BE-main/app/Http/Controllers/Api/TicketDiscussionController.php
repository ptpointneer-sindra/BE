<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketDiscussion;
use App\Models\TicketDiscussionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketNotificationMail;
use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketDiscussionController extends Controller
{
    public function index($ticketId)
    {
        $discussions = TicketDiscussion::with(['attachments', 'user'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($discussions);
    }

    public function store(Request $request, $ticketId)
    {
        try{

        
        DB::beginTransaction();

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'file|max:2048',
        ]);

        $ticket = Ticket::with(['reporter'])->findOrFail($ticketId);

        $discussion = TicketDiscussion::create([
            'ticket_id' => $ticketId,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        // Simpan attachment jika ada
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-discussions', 'public');
                TicketDiscussionAttachment::create([
                    'discussion_id' => $discussion->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }

         /**
         * 🔔 Kirim Email Notifikasi
         */
        $currentUser = auth()->user();

        // Cek apakah pengirim adalah reporter atau bukan
        $isReporter = $ticket->reporter_id === $currentUser->id;

        if ($isReporter) {
            // Kirim ke semua assignees
            $assignees = TicketAssignee::with('user')
                ->where('ticket_id', $ticket->id)
                ->get();

            foreach ($assignees as $assignee) {
                if ($assignee->user && $assignee->user->email) {
                    Mail::to($assignee->user->email)->send(
                        new TicketNotificationMail(
                            $ticket,
                            'Diskusi Baru dari Reporter',
                            "Reporter menambahkan komentar baru pada tiket: \n\n{$request->message}"
                        )
                    );
                }
            }
        } else {
            // Kirim ke reporter
            if ($ticket->reporter && $ticket->reporter->email) {
                Mail::to($ticket->reporter->email)->send(
                    new TicketNotificationMail(
                        $ticket,
                        'Balasan dari Petugas',
                        "{$currentUser->name} menambahkan komentar baru:\n\n{$request->message}"
                    )
                );
            }
        }

        TicketLog::addLog($ticket->id, Auth::id(), "Membuat Pesan Baru ");
        
        DB::commit();

        return response()->json([
            'message' => 'Discussion added successfully',
            'data' => $discussion->load('attachments', 'user'),
        ]);
        }catch(\Exception $e){

            DB::rollBack();
            return response()->json([
            'message' => $e->getMessage(),
            
        ]);
        }
    }

    public function destroy($id)
    {
        $discussion = TicketDiscussion::findOrFail($id);

        // Hapus attachments dari storage
        foreach ($discussion->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        $discussion->delete();

        
        TicketLog::addLog($discussion->ticket_id, Auth::id(), "Menghapus Pesan ");

        return response()->json(['message' => 'Discussion deleted successfully']);
    }
}
