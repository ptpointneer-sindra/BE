<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketNotificationMail;
use App\Models\TicketAssignee;
use App\Mail\TicketStatusChangedMail;
use App\Models\RequestChange;
use App\Models\RequestChangeAttachment;
use App\Models\TicketLog;
use App\Services\TicketService;
use App\Traits\ApiResponses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    use ApiResponses;

    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
 * @OA\Tag(
 *     name="Tickets",
 *     description="Manajemen tiket pengaduan kerusakan barang"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/tickets",
 *     tags={"Tickets"},
 *     summary="Ambil semua tiket",
 *     description="Menampilkan semua tiket beserta relasi reporter, assignees, dan attachments",
 *     @OA\Response(
 *         response=200,
 *         description="Daftar tiket berhasil diambil",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Ticket"))
 *     )
 * )
 */

    public function index(Request $request)
    {
        $response =  $this->ticketService->getAllTickets($request);
        

        return $this->sendSuccessResponse($response, 'Tickets retrieved successfully',200);
    }


/**
 * @OA\Post(
 *     path="/api/tickets",
 *     tags={"Tickets"},
 *     summary="Buat tiket baru",
 *     description="Membuat tiket pengaduan baru lengkap dengan assignee dan attachment",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"asset_uuid", "reporter_id", "title"},
 *                 @OA\Property(property="asset_uuid", type="string", example="b2b1a570-4e24-4ac7-a1bb-4e58a6c9471d"),
 *                 @OA\Property(property="reporter_id", type="integer", example=10),
 *                 @OA\Property(property="title", type="string", example="Printer tidak menyala"),
 *                 @OA\Property(property="description", type="string", example="Printer Epson rusak di ruang 201"),
 *                 @OA\Property(property="status", type="string", enum={"open","in_progress","resolved","closed"}, example="open"),
 *                 @OA\Property(property="assignees[]", type="array", @OA\Items(type="integer", example=5)),
 *                 @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Tiket berhasil dibuat",
 *         @OA\JsonContent(ref="#/components/schemas/Ticket")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validasi gagal"
 *     )
 * )
 */
    public function store(Request $request)
    {
        $request->validate([
            'asset_id'   => 'required',
            'title'        => 'required|string|max:255',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'instansi_id'  => 'required|exists:instansis,id',
            'description'  => 'nullable|string',
            'attachments.*'=> 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();

        $priority = $this->classify(
            $request->title,
            $request->description,
            $request->ticket_category_id
        );

        try {
            $ticket = Ticket::create([
            'asset_uuid'         => $request->asset_id,
            'reporter_id'        => auth()->id(),
            'title'              => $request->title,
            'ticket_category_id' => $request->ticket_category_id,
            'instansi_id'        => $request->instansi_id,
            'description'        => $request->description, 
            'status'             => 'open',
            'priority'           => $priority,
        ]);

               // Kirim email ke reporter
        $reporter = $ticket->reporter;
        if ($reporter && $reporter->email) {
            Mail::to($reporter->email)->send(
                new TicketNotificationMail(
                    $ticket,
                    'Tiket Baru Diajukan',
                    'Tiket Anda telah berhasil dibuat dan sedang menunggu tindak lanjut.'
                )
            );
        }

            // Upload file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('tickets', 'public');

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $path,
                        'file_url' => url('/' . $path) ,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }


            DB::commit();
            return response()->json(['message' => 'Ticket created successfully', 'data' => $ticket->load(['assignees', 'attachments'])], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


/**
 * @OA\Get(
 *     path="/api/tickets/{id}",
 *     tags={"Tickets"},
 *     summary="Detail tiket",
 *     description="Menampilkan detail lengkap tiket berdasarkan ID termasuk reporter, assignees, attachments, dan discussions",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID tiket",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Detail tiket berhasil diambil",
 *         @OA\JsonContent(ref="#/components/schemas/Ticket")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Tiket tidak ditemukan"
 *     )
 * )
 */

    public function show($id)
    {
        $ticket = Ticket::with(['reporter', 'assignees', 'attachments', 'discussions', 'category', 'log', 'feedback', 'instansi', 'reopen'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
 * @OA\Post(
 *     path="/api/tickets/{id}",
 *     tags={"Tickets"},
 *     summary="Perbarui tiket",
 *     description="Update judul, deskripsi, status, assignees, atau upload attachment baru",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID tiket yang akan diupdate",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="title", type="string", example="Printer mati total"),
 *                 @OA\Property(property="description", type="string", example="Sudah dicek kabel dan tombol power"),
 *                 @OA\Property(property="status", type="string", enum={"open","in_progress","resolved","closed"}, example="in_progress"),
 *                 @OA\Property(property="assignees[]", type="array", @OA\Items(type="integer", example=8)),
 *                 @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Tiket berhasil diperbarui",
 *         @OA\JsonContent(ref="#/components/schemas/Ticket")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Tiket tidak ditemukan"
 *     )
 * )
 */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $oldStatus = $ticket->status;


        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'in:open,in_progress,resolved,closed',
            'type'        => 'sometimes|string|in:incident,service_request', // tambah validasi opsional
            'assignees'   => 'array',
            'assignees.*' => 'exists:users,id',
            'attachments.*'=> 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $ticket->update($request->only(['title', 'description', 'status']));

            // update type JIKA dikirim di body
            if ($request->has('type')) {
                $ticket->type = $request->type;
                $ticket->save();

            }

            if ($request->filled('assignees')) {
                $ticket->assignees()->sync($request->assignees);
                
            }

              // Ambil semua assignees
        $assignees = TicketAssignee::with('user')->where('ticket_id', $ticket->id)->get();

        // Kirim email ke semua assignees
        foreach ($assignees as $assignee) {
            $user = $assignee->user;
            TicketLog::addLog($id,$assignee->user_id, "Berhasil Menambahkan " . $user->name ." Kedalam Ticket", "Menambahkan ke ticket");
            
            if ($user && $user->email) {
                Mail::to($user->email)->send(
                    new TicketNotificationMail(
                        $ticket,
                        'Tiket Diperbarui',
                        'Ada pembaruan pada tiket yang sedang Anda tangani.'
                    )
                );
            }
        }

         // Kirim email ke reporter juga
        if ($ticket->reporter && $ticket->reporter->email) {
            Mail::to($ticket->reporter->email)->send(
                new TicketNotificationMail(
                    $ticket,
                    'Tiket Diperbarui',
                    'Status atau detail tiket Anda telah diperbarui.'
                )
            );
        }


            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('tickets', 'public');

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ]);

                    TicketLog::addLog($ticket->id, Auth::id(), "Menambahkan Attachment Baru ");

                }
            }

                // Cek perubahan status
                if ($oldStatus !== $ticket->status) {
                    // Kirim email ke reporter
                    if ($ticket->reporter && $ticket->reporter->email) {
                        Mail::to($ticket->reporter->email)
                            ->send(new TicketStatusChangedMail($ticket, $oldStatus, $ticket->status));
                    }

                    // Kirim email ke semua assignee
                    foreach ($ticket->assignees as $assignee) {
                        if ($assignee->user && $assignee->user->email) {
                            Mail::to($assignee->user->email)
                                ->send(new TicketStatusChangedMail($ticket, $oldStatus, $ticket->status));
                        }
                    }

                    $rfc = RequestChange::where('ticket_id', $ticket->id)->first();

                    // Hanya kirim jika RFC memang ada
                    if ($rfc) {
                        $this->sendReportToConfig($ticket->id, $rfc->id);
                    }

                }

            
            DB::commit();
            return response()->json(['message' => 'Ticket updated successfully', 'data' => $ticket->load(['assignees', 'attachments'])]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

/**
 * @OA\Delete(
 *     path="/api/tickets/{id}",
 *     tags={"Tickets"},
 *     summary="Hapus tiket",
 *     description="Menghapus tiket beserta semua file attachment-nya",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID tiket yang ingin dihapus",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Tiket berhasil dihapus"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Tiket tidak ditemukan"
 *     )
 * )
 */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);

        foreach ($ticket->attachments as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    public function getTicketCountSummary(){
        $counts = $this->ticketService->getTicketStatusSummary();

        return response()->json([
            'message' => '',
            'data' => [
                'counts' => $counts,
                'last_sync' => now()->toISOString()
            ]
        ]);
    } 

    public function getReportTickets(){
        $response = $this->ticketService->getReportTickets();

        return response()->json([
            'status' => 'success',
            'message' => 'success get data',
            'data' => $response['data']
        ]);
    }

    public function sendReportToConfig($ticketId, $rfcId)
    {
        // Fix: exists()
        $rfcExist = RequestChange::where('ticket_id', $ticketId)->exists();
        $ticket = Ticket::find($ticketId);

        if (!$ticket || !$rfcExist) {
            return false;
        }

        $rfc = RequestChange::find($rfcId);

        if (!$rfc) {
            return false;
        }

        if($rfc->status !== 'approved'){
            return false;
        }

        // Tentukan status implementasi
        if ($ticket->status == 'resolved') {
            $statusImplement = 'success';
        } elseif ($ticket->status == 'closed') {
            $statusImplement = 'failed';
        } else {
            return false;
        }

        // Update RFC
        $rfc->update([
            'status_implement' => $statusImplement
        ]);

        // Ambil attachment RFC
        $attachments = RequestChangeAttachment::where('request_change_id', $rfcId)
        ->pluck('file_path')
        ->map(function ($path) {
            return url($path);
        })
        ->toArray();


        // Payload yang dikirim
        $payload = [
            'rfc_service_id' => (string)$rfc->id,
            'status'         => $statusImplement,
            'description'    => $rfc->description,
            'attachments'    => $attachments,
            'completed_at'   => $rfc->resolved_at ?? now(),
        ];

        $headers = [
            'Accept'    => 'application/json'
        ];

        // Domain Config Service
        $domain = rtrim(env('CONFIG_URL', 'https://api.simantic.online'), '/');

        Log::info("TicketController@sendReportConfig Payload INFO :",[
            'payload' => $payload, 
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->post($domain . '/api/v1/implementation-reports', $payload);

            Log::info("TicketController@sendReportConfig Response API : ",[
                'response' => $response->body()
            ]);
            return $response->json();

        } catch (\Exception $e) {
            // Opsional: simpan log error
            Log::error("Failed to send implementation report", [
                'ticket_id' => $ticketId,
                'rfc_id'    => $rfcId,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getPerOpdTicketReport(Request $request)
    {
        $response = $this->ticketService->getPerOpdTicketReport($request->query('instansi_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'success get data',
            'data' => $response
        ]);
    }

    public  function classify(
        string $title,
        ?string $description,
        int $ticketCategoryId
    ): string
    {
        $oneHourAgo = Carbon::now()->subHour();

        $ticketCount = Ticket::where('ticket_category_id', $ticketCategoryId)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        // ticket ke-5 => count sudah 4 sebelumnya
        if ($ticketCount >= 4) {
            return 'critical';
        }

        // 🔹 RULE 2: Keyword based
        $text = strtolower($title . ' ' . ($description ?? ''));

        $highKeywords = [
            'server down',
            'tidak bisa login',
            'error 500',
            'database',
            'production',
            'tidak bisa akses',
            'crash'
        ];

        $mediumKeywords = [
            'lambat',
            'bug',
            'error',
            'gagal',
            'timeout'
        ];

        foreach ($highKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'high';
            }
        }

        foreach ($mediumKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'medium';
            }
        }

        return 'low';
    }

}
