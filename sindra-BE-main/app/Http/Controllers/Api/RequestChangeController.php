<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequestChange;
use App\Models\RequestChangeAttachment;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RequestChangeController extends Controller
{
    /**
     * GET ALL REQUEST CHANGES
     */
    public function index()
    {
        $data = RequestChange::with(['reporter', 'ticket', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'List Request Changes',
            'data' => $data
        ]);
    }

    /**
     * CREATE NEW REQUEST CHANGE
     */
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            // 'reporter_id' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4|max:50000',
        ]);

        $requestChange = RequestChange::create([
            'ticket_id' => $request->ticket_id,
            'reporter_id' => Auth::id(),
            'asset_uuid' => Str::uuid(),
            'description' => $request->description,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // === Upload Attachments ===
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {

                $path = $file->store('request_changes', 'public');

                RequestChangeAttachment::create([
                    'request_change_id' => $requestChange->id,
                    'file_path' => $path,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Request Change created successfully',
            'data' => $requestChange->load('attachments')
        ]);
    }

    /**
     * SHOW SINGLE REQUEST CHANGE
     */
    public function show($id)
    {
        $data = RequestChange::with(['reporter', 'ticket', 'attachments'])
            ->find($id);

        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail Request Change',
            'data' => $data
        ]);
    }

    /**
     * UPDATE REQUEST CHANGE
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'nullable|string',
            'status' => 'in:pending,submitted,approved,rejected',
            'config_comment' => 'nullable|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4|max:50000',
        ]);

        $requestChange = RequestChange::find($id);

        if (!$requestChange) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        $requestChange->update([
            'description' => $request->description ?? $requestChange->description,
            'status' => $request->status ?? $requestChange->status,
            'config_comment' => $request->config_comment ?? $requestChange->config_comment,
            'reviewed_at' => in_array($request->status, ['approved', 'rejected']) ? now() : null,
        ]);

        // === Add new attachments ===
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('request_changes', 'public');

                RequestChangeAttachment::create([
                    'request_change_id' => $requestChange->id,
                    'file_path' => $path,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Request Change updated successfully',
            'data' => $requestChange->load('attachments')
        ]);
    }

    /**
     * DELETE REQUEST CHANGE + ATTACHMENTS
     */
    public function destroy($id)
    {
        $requestChange = RequestChange::with('attachments')->find($id);

        if (!$requestChange) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        // Delete file from storage
        foreach ($requestChange->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
        }

        $requestChange->delete(); // Cascade delete attachments

        return response()->json([
            'status' => true,
            'message' => 'Request Change deleted successfully',
        ]);
    }

    public function sendToConfig($id)
    {
        $RFC = RequestChange::with('attachments')
            ->find($id);

        if (!$RFC) {
        return response()->json(['status' => false, 'message' => 'RFC not found'], 404);
        }

        if($RFC->status !== 'pending'){
            return response()->json([
                'status' => false,
                'message' => 'RFC has already been submitted or processed',
            ], 400);
        }

        // Siapkan attachments full URL
        $attachments = $RFC->attachments->map(function ($file) {
            return asset($file->file_path); // asset() bikin full URL
        })->toArray();

        $payload = [
            'rfc_service_id' => (string) $RFC->id,
            'asset_uuid' => $RFC->asset_uuid,
            'title' => 'RFC for Ticket #'.$RFC->ticket->code,
            'description' => $RFC->description,
            'priority' => $RFC->ticket->priority,
            'requested_at' => $RFC->requested_at->toDateTimeString(),
            'attachments' => $attachments,
            'sso_id' => $RFC->reporter->sso_id,
        ];

        $headers = [
            'X-TOKEN' => env('APP_TOKEN','dummy-token')
        ];

        $domain = env('CONFIG_URL', 'https://api.simantic.online');

        $response = Http::withHeaders($headers)
            ->post($domain.'/api/v1/rfc', $payload)->json();

        if($response['status'] == true){
            $RFC->update([
                'status' => 'submitted',
                'request_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'RFC sent to Configuration System successfully',
                'data' => $response['data']
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Failed to send RFC to Configuration System',
                'error' => $response['message'] ?? 'Unknown error'
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $request->validate([
            'rfc_service_id' => 'required|integer',
            'status' => 'required|in:approved,rejected',
            'config_comment' => 'nullable|string',
            'ci_code' => 'required|string'
        ]);

        $RFC = RequestChange::find($request->rfc_service_id);

        if (!$RFC) {
            return response()->json(['status' => false, 'message' => 'RFC not found'], 404);
        }

        if($RFC->status !== 'submitted'){
            return response()->json([
                'status' => false,
                'message' => 'RFC is not in a submitted state',
            ], 400);
        }

        $RFC->update([
            'status' => $request->status,
            'config_comment' => $request->config_comment,
            'ci_code' => $request->ci_code,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'RFC status updated successfully',
            'data' => $RFC
        ]);
    }
}
