<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class TicketExportController extends Controller
{
    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $tickets = $this->filterTickets($request);

        return Excel::download(new TicketsExport($tickets), 'tickets.xlsx');
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $tickets = $this->filterTickets($request);

        $pdf = Pdf::loadView('exports.tickets', [
            'tickets' => $tickets
        ]);

        return $pdf->download('tickets.pdf');
    }

    /**
     * FILTER TICKETS (role + status + priority + category)
     */
    private function filterTickets($request)
    {
        $user = Auth::user();
        $role = $user->role;

        $query = Ticket::query()->with(['reporter','category','assignees']);

        // ROLE: user hanya tiket yang dibuat dia
        if ($role === 'user') {
            $query->where('reporter_id', $user->id);
        }

        // ROLE: teknisi/admin hanya tiket yang diassign ke dia
        if (in_array($role, ['admin-bidang','admin-seksi','teknisi'])) {
            $query->whereHas('assignees', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // FILTER status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // FILTER priority
        if ($request->priority) {
            $query->where('priority_id', $request->priority);
        }

        // FILTER category
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        return $query->get();
    }
}
