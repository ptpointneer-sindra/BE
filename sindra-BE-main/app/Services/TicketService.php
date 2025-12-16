<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketService
{

    public function getAllTickets($request)
    {
        $user = Auth::user();
        $role = $user->role;

        $perPage = $request->input('per_page', 10);
        $priority = $request->input('priority');
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');
        $instansiId = $request->input('instansi');

        $tickets = Ticket::with(['reporter', 'assignees', 'attachments', 'instansi', 'category', 'escalates', 'feedback'])
            ->latest()

            // FILTER BERDASARKAN ROLE
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })

            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })

            // admin-opd & admin-kota → tidak diberi where (ambil semua)

            // PRIORITY FILTER
            ->when($priority, function ($query) use ($priority) {
                if ($priority != 'all' || $priority != -1) {
                    $query->where('priority', $priority);
                }
            })
            ->when($instansiId, function ($query) use ($instansiId) {
                if ($instansiId != 'all' || $instansiId != -1) {
                    $query->where('instansi_id', $instansiId);
                }
            })

            // SEARCH FILTER
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })

            // STATUS FILTER
            ->when($status, function ($query) use ($status) {
                if ($status != 'active') {
                    $query->where('status', $status);
                } else if ($status == 'all') {

                } else if ($status == 'closed') {
                    $query->whereIn('status', ['closed', 'resolved']);
                } else if ($status == 'active-assigned') {
                    $query->whereNotIn('status', ['open', 'closed', 'resolved']);
                } else {
                    $query->whereNotIn('status', ['closed', 'resolved']);
                }
            })

            ->when($category, function ($query) use ($category) {
                if ($category != 'all' || $category != -1) {
                    $query->where('ticket_category_id', $category);
                }
            });

        $summary = [];
        if ($role === 'admin-opd') {
            $adminDataset = (clone $tickets)->select('status')->get();


            $summary['new_count'] = $adminDataset->where('status', 'open')->count();
            $summary['pending_count'] = $adminDataset->where('status', 'pending')->count();
            $summary['resolved'] = $adminDataset->whereIn('status', ['closed', 'resolved'])->count();
        } else if ($role === 'user') {
            $summary['active_count'] = Ticket::whereNotIn('status', ['closed', 'resolved'])->where('reporter_id', $user->id)->count();

            $summary['waiting_count'] = Ticket::where('status', 'resolved')->where('reporter_id', $user->id)->count();

            $summary['resolved_count'] = Ticket::whereIn('status', ['closed', 'resolved'])
                ->where('reporter_id', $user->id)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();
        }

        $tickets = $tickets->paginate($perPage);


        $response = $tickets->toArray();

        $response['summary'] = $summary;

        return $response;
    }


    public function getAllTicketsByReporterId($reporterId, $request)
    {
        $perPage = $request->input('per_page', 10);
        $priority = $request->input('priority');
        $search = $request->input('search');
        $status = $request->input('status');


        $tickets = Ticket::with(['reporter', 'assignees', 'attachments'])
            ->where('reporter_id', $reporterId)
            ->when($priority, function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return $tickets;
    }

    public function getActiveTicketsCount()
    {
        return Ticket::where('status', '!=', 'closed')->count();
    }

    public function getTicketStatusSummary()
    {
        $user = Auth::user();
        $role = $user->role;

        $statuses = Ticket::with(['assignees'])
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
        ->select(DB::raw("
            CASE 
                WHEN status = 'in_progress' AND deadline_at < '" . Carbon::now()->addDay() . "' 
                THEN 'approaching'
                ELSE status
            END as status_group
        "), DB::raw('COUNT(*) as total'))
            ->groupBy('status_group')
            ->orderBy('status_group')
            ->pluck('total', 'status_group');

        return $statuses;
    }

    public function getReportTickets()
    {
        $user = Auth::user();
        $role = $user->role;

        $now = Carbon::now();
        $sub4hours = Carbon::now()->subHours(4);
        $next4Hours = Carbon::now()->addHours(4);

        /** 
         * BASE QUERY (digunakan ulang)
         */
        $baseQuery = Ticket::with('assignees')
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });

        /** 
         * TOTAL ALL TICKETS 
         */
        $all = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress', 'resolved', 'closed'])
            ->count();

        /**
         * RESOLVED
         */
        $resolved = (clone $baseQuery)
            ->where('status', 'resolved')
            ->count();

        /**
         * OVERDUE — deadline lewat
         * (harusnya: deadline_at < now)
         */
        $overdue = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress', 'resolved', 'closed'])
            ->where('deadline_at', '<', $now)
            ->count();

        /**
         * COMPLIANCE — belum urgent (deadline > now-4h)
         */
        $compliance = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereBetween('deadline_at', [Carbon::now(), Carbon::now()->addHours(4)])
            ->count();


        /**
         * AVERAGE RESOLUTION TIME
         * hitung selisih created_at → resolved_at
         */
        $averageResolutionPercent = $all > 0 
            ? round(($resolved / $all) * 100, 2)
            : 0;


        return [
            'data' => [
                'all' => $all,
                'resolved' => $resolved,
                'overdue' => $overdue,
                'compliance' => $compliance,
                'averageResolutionPercent' => $averageResolutionPercent,
            ]
        ];
    }

    public function getPerOpdTicketReport($opdId)
    {
        $base = Ticket::query();

        $base->when($opdId, function ($query) use ($opdId) {
            $query->whereHas('assignees', function ($q) use ($opdId) {
                $q->where('instansi_id', $opdId);
            });
        });

        $now = now();

        $counts = (clone $base)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as handled,
                SUM(CASE WHEN status IN ('open','in_progress') THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status IN ('open','in_progress') AND deadline_at < ? THEN 1 ELSE 0 END) as unfinished
            ", [$now])
            ->first();

        $priorityCounts = (clone $base)
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $averageRating = (clone $base)
            ->whereHas('feedback')
            ->join('ticket_feedbacks', 'tickets.id', '=', 'ticket_feedbacks.ticket_id')
            ->avg('ticket_feedbacks.rating');

        $avgResolutionTime = (clone $base)
            ->whereHas('feedback')
            ->join('ticket_feedbacks', 'tickets.id', '=', 'ticket_feedbacks.ticket_id')
            ->selectRaw("AVG(TIMESTAMPDIFF(SECOND, tickets.created_at, ticket_feedbacks.created_at)) as avg_seconds")
            ->value('avg_seconds');

        $avgDuration = $avgResolutionTime ? gmdate('H:i:s', $avgResolutionTime) : null;

        return [
            'total' => $counts->total,
            'handled' => $counts->handled,
            'in_progress' => $counts->in_progress,
            'unfinished' => $counts->unfinished,

            'priority' => $priorityCounts,

            'average_rating' => $averageRating,
            'average_resolution_time_seconds' => $avgResolutionTime,
            'average_resolution_time_readable' => $avgDuration,
        ];
    }
}
