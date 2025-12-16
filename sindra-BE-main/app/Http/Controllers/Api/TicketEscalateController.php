<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TicketNotificationMail;
use App\Models\TicketEscalate;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TicketEscalateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $status = $request->input('status', 'pending');
        $perPage = $request->input('per_page', 10);
        
        $user = Auth::user();
        $query = TicketEscalate::with(['ticket', 'ticket.category', 'ticket.instansi', 'ticket.reporter']);

        // Auto-filter status pending
        $query->where('status', $status);

        // Filter berdasarkan role
        switch ($user->role) {
            case 'admin-seksi':
                $query->where('destination', 'seksi');
                break;

            case 'admin-bidang':
                $query->where('destination', 'bidang');
                break;

            case 'admin-opd':
                $query->where('destination', 'opd');
                break;

            case 'admin-kota':
                // admin-kota boleh melihat semua, jadi tidak di-filter
                break;

            default:
                return response()->json([
                    'message' => 'Unauthorized role'
                ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage)->toArray()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id'   => 'required|integer',
            'description' => 'nullable|string',
            'destination' => 'required|in:seksi,bidang,kota,opd',
        ]);

        $escalate = TicketEscalate::create([
            'ticket_id'   => $request->ticket_id,
            'description' => $request->description,
            'destination' => $request->destination,
            'status'      => 'pending',
        ]);

        TicketLog::addLog(
            $request->ticket_id,
            Auth::id(),
            'Ticket Escalated',
            'Ticket escalated to ' . $request->destination
        );

        return response()->json($escalate, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $escalate = TicketEscalate::find($id)->load(['ticket', 'ticket.category', 'ticket.instansi', 'ticket.reporter']);

        if (!$escalate) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($escalate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $escalate = TicketEscalate::find($id);

        if (!$escalate) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $request->validate([
            'description' => 'nullable|string',
            'status'      => 'nullable|in:pending,approve,rejected',
        ]);

        $oldStatus = $escalate->status;                     // status sebelum update
        $newStatus = $request->status ?? $escalate->status; // status sesudah update
        $userId    = Auth::id();                         // user yang approve/reject

        // 🔹 Update escalate record
        $escalate->update([
            'description' => $request->description ?? $escalate->description,
            'status'      => $newStatus,
        ]);

        // 🔥 Jika status berubah dari pending → approved
        if ($oldStatus === 'pending' && $newStatus === 'approve') {
            
            // Cek apakah user ini sudah menjadi assignee
            $exists = DB::table('ticket_assignees')
                ->where('ticket_id', $escalate->ticket_id)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                DB::table('ticket_assignees')->insert([
                    'ticket_id'  => $escalate->ticket_id,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $reporter = User::find($escalate->ticket->reporter_id);

            if ($reporter && $reporter->email) {

                // 3. Subject & pesan email
                $subject = "Status Ticket Anda Telah Disetujui (#" . $escalate->ticket->code . ")";
                $message = "Ticket Anda telah disetujui dan sedang diproses oleh tim terkait.";

                // 4. Kirim email ke REPORTER
                Mail::to($reporter->email)
                    ->send(new TicketNotificationMail($escalate->ticket, $subject, $message));
            }

            TicketLog::addLog(
                $escalate->ticket_id,
                $userId,
                'Ticket Escalated Approved By :' . Auth::user()->name,
                'Ticket escalation approved and assignee updated.'
            );
        }

        return response()->json($escalate);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $escalate = TicketEscalate::find($id);

        if (!$escalate) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $escalate->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
