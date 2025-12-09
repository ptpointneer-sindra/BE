<?php 

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CalenderService
{
    public function deadlineSummaryCount()
    {
        $user = Auth::user();
        $role = $user->role;

        // Base Query (filter role + status)
        $baseQuery = Ticket::when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->whereIn('status', ['open', 'in_progress', 'resolved', 'closed']);

        // Today
        $today = (clone $baseQuery)
            ->whereDate('deadline_at', Carbon::today())
            ->count();

        // This Week (deadline jatuh minggu ini)
        $week = (clone $baseQuery)
            ->whereBetween('deadline_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])
            ->count();

        // This Month
        $month = (clone $baseQuery)
            ->whereBetween('deadline_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();

        // This Year
        $year = (clone $baseQuery)
            ->whereYear('deadline_at', Carbon::now()->year)
            ->count();

        return [
            'data' => [
                'today' => $today,
                'week' => $week,
                'month' => $month,
                'year' => $year,
            ]
        ];
    }

    public function performanceCount()
    {
        $user = Auth::user();
        $role = $user->role;

        // Base Query (filter role)
        $baseQuery = Ticket::when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at');

        // On-Time: resolved_at <= deadline_at
        $onTime = (clone $baseQuery)
            ->whereColumn('resolved_at', '<=', 'deadline_at')
            ->count();

        // Overdue: resolved_at > deadline_at
        $overdue = (clone $baseQuery)
            ->whereColumn('resolved_at', '>', 'deadline_at')
            ->count();

        return [
            'data' => [
                'onTime' => $onTime,
                'overdue' => $overdue
            ]
        ];
    }

    public function todayTask()
    {
        $user = Auth::user();
        $role = $user->role;

        $today = now()->toDateString(); // '2025-12-07'

        $tickets = Ticket::with(['assignees'])
            ->whereDate('deadline_at', $today)

            // FILTER BERDASARKAN ROLE
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })

            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->latest()
            ->get();

        $tickets->transform(function ($ticket) {
        $deadline = Carbon::parse($ticket->deadline_at);
        $now = now();

        // selisih jam bisa minus (jika sudah lewat)
        $ticket->remainingHours = round($now->diffInHours($deadline, false),1);

        return $ticket;
    });


        return [
            'data' => $tickets
        ];
    }


}