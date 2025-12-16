<?php

namespace App\Http\Controllers;

use App\Mail\TicketNotificationMail;
use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Models\TicketFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TicketFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id'   => 'required|exists:tickets,id',
            'rating'      => 'required|decimal:1,5',
            'feedback' => 'nullable|string|max:500'
        ]);

        $feedback = TicketFeedback::create([
            'ticket_id'   => $validated['ticket_id'],
            'user_id'     => Auth::id(),
            'rating'      => $validated['rating'],
            'feedback' => $validated['feedback'] ?? null,
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $assignees = TicketAssignee::with('user')->where('ticket_id', $validated['ticket_id'])->get();

        // Kirim email ke semua assignees
        foreach ($assignees as $assignee) {
            $user = $assignee->user;
            if ($user && $user->email) {
                Mail::to($user->email)->send(
                    new TicketNotificationMail(
                        $ticket,
                        'Tiket Selesai',
                        'Ticket Selesai, telah di rating pengguna'
                    )
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil disimpan.',
            'data'    => $feedback
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $feedback = TicketFeedback::with(['ticket', 'user'])->find($id);

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feedback
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
