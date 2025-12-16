<?php

namespace App\Http\Controllers\Api;

use App\Models\TicketLog;
use App\Models\TicketReopen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class TicketReopenController extends Controller
{
    /**
     * List all reopen requests
     */
    public function index()
    {
        $data = TicketReopen::latest()->with('ticket')->paginate(20);
        return response()->json($data);
    }

    /**
     * Create new reopen request
     */
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|integer',
            'reason'    => 'required|string|max:255',
            'detail'    => 'nullable|string',
            'attachment'=> 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status'    => 'required|string',
        ]);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reopen_attachments', 'public');
        }

        $reopen = TicketReopen::create([
            'ticket_id' => $request->ticket_id,
            'reason'    => $request->reason,
            'detail'    => $request->detail,
            'attachment'=> $attachmentPath,
            'status'    => $request->status,
        ]);

        TicketLog::addLog($request->ticket_id, Auth::id(), "Membuat Re-open Ticket");

        return response()->json([
            'message' => 'Ticket reopen request created successfully',
            'data'    => $reopen
        ]);
    }

    /**
     * Show detail reopen request
     */
    public function show($id)
    {
        $data = TicketReopen::with(['ticket', 'ticket.reporter', 'ticket.category'])->findOrFail($id);
        return response()->json($data);
    }

    /**
     * Update reopen request
     */
    public function update(Request $request, $id)
    {
        $reopen = TicketReopen::findOrFail($id);

        $request->validate([
            'reason'    => 'sometimes|string|max:255',
            'detail'    => 'sometimes|string',
            'attachment'=> 'sometimes|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status'    => 'sometimes|string',
        ]);

        $oldStatus = $reopen->status;

        if ($request->hasFile('attachment')) {
            // delete old file
            if ($reopen->attachment) {
                Storage::disk('public')->delete($reopen->attachment);
            }

            $reopen->attachment = $request->file('attachment')->store('reopen_attachments', 'public');
        }

        $reopen->update($request->only([
            'reason', 'detail', 'status'
        ]));

        
        if ($request->status && $oldStatus !== $request->status) {
            TicketLog::addLog(
                $reopen->ticket_id,
                Auth::id(),
                "Update Status Reopen Ticket",
                "Status berubah dari '{$oldStatus}' menjadi '{$request->status}'"
            );
        }


        return response()->json([
            'message' => 'Ticket reopen request updated successfully',
            'data'    => $reopen
        ]);
    }

    /**
     * Delete reopen request
     */
    public function destroy($id)
    {
        $reopen = TicketReopen::findOrFail($id);

        if ($reopen->attachment) {
            Storage::disk('public')->delete($reopen->attachment);
        }

        $reopen->delete();

        TicketLog::addLog(
                $reopen->ticket_id,
                Auth::id(),
                "Menghapus Re-open Ticket",
            );

        return response()->json([
            'message' => 'Ticket reopen request deleted successfully'
        ]);
    }
}
