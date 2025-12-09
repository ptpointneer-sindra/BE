<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\CalenderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalenderController extends Controller
{
    protected $calenderService;

    public function __construct(CalenderService $calenderService)
    {
        $this->calenderService = $calenderService;
    }

    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $user = Auth::user();
        $role = $user->role;

        // Ambil semua ticket bulan & tahun
        $ticketsQuery = Ticket::with(['assignees'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });

        $tickets = $ticketsQuery->get();

        // Generate semua tanggal dalam 1 bulan
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = (clone $startDate)->endOfMonth();

        $days = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            $dailyTickets = $tickets->filter(function($t) use ($date) {
                return Carbon::parse($t->created_at)->isSameDay($date);
            });

            $hasTask = $dailyTickets->isNotEmpty();
            $completed = $hasTask && $dailyTickets->every(fn($t) => $t->status === 'completed');

            $warning = $dailyTickets->contains(function($t) {
                return Carbon::parse($t->deadline)->isToday() ||
                    Carbon::parse($t->deadline)->diffInDays(now()) <= 3;
            });

            $overdue = $dailyTickets->contains(function($t) {
                return Carbon::parse($t->deadline)->isPast() &&
                    $t->status !== 'completed';
            });

            $days[] = [
                'date' => $date->toDateString(),
                'task' => $hasTask,
                'warning' => $warning,
                'overdue' => $overdue,
                'completed' => $completed,
            ];
        }

        return response()->json([
            'success' => true,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year' => (string) $year,
            'data' => $days
        ]);
    }

    public function showByDate($date)
    {

        if (!strtotime($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use YYYY-MM-DD'
            ], 400);
        }

        $date = Carbon::parse($date);

        $user = Auth::user();
        $role = $user->role;

        // Base query
        $ticketsQuery = Ticket::with([
            'assignees',
            'category:id,name'
            ])
            ->whereDate('created_at', $date->toDateString())
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->select(
                'id',
                'title',
                'resolved_at',
                'ticket_category_id',
            );

        $tickets = $ticketsQuery->get();

        // --- Classification ---
        $completedTickets = $tickets->filter(function ($t) {
            return $t->status === 'completed';
        });

        $warningTickets = $tickets->filter(function ($t) {
            return Carbon::parse($t->deadline)->isToday()
                || Carbon::parse($t->deadline)->diffInDays(now()) <= 3;
        });

        $overdueTickets = $tickets->filter(function ($t) {
            return Carbon::parse($t->deadline)->isPast() &&
                $t->status !== 'completed';
        });

        $taskTickets = $tickets->filter(function ($t) {
            return $t->status === 'scheduled';
        });

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'total' => $tickets->count(),
            'data' => [
                'completed_count'   => $completedTickets->count(),
                'completed_tickets' => $completedTickets->values(),

                'warning_count'     => $warningTickets->count(),
                'warning_tickets'   => $warningTickets->values(),

                'overdue_count'     => $overdueTickets->count(),
                'overdue_tickets'   => $overdueTickets->values(),

                'task_count'   => $taskTickets->count(),
                'task_tickets' => $taskTickets->values(),
            ]
        ]);
    }

    public function deadlineSummaryCount(){
        $response = $this->calenderService->deadlineSummaryCount();

        return response()->json([
            'status' => 'success',
            'message' => 'OK',
            'data' => $response['data']
        ]);
    }

    public function performanceCount(){

        $response = $this->calenderService->performanceCount();

        return response()->json([
            'status' => 'success',
            'message' => 'OK',
            'data' => $response['data']
        ]);
    }

    public function todayTask(){
        $response = $this->calenderService->todayTask();

        return response()->json([
            'status' => 'success',
            'message' => 'OK',
            'data' => $response['data']
        ]);
    }

}
