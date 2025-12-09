<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function index($ticketId)
    {
        $attachments = TicketAttachment::where('ticket_id', $ticketId)->get();
        return response()->json($attachments);
    }

    public function store(Request $request, $ticketId)
    {
        $request->validate([
            'files.*' => 'required|file|max:2048',
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('ticket-attachments', 'public');
            $uploadedFiles[] = TicketAttachment::create([
                'ticket_id' => $ticketId,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json([
            'message' => 'Attachments uploaded successfully',
            'data' => $uploadedFiles,
        ]);
    }

    public function destroy($id)
    {
        $attachment = TicketAttachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }
}
