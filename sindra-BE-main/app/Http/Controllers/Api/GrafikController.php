<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GrafikController extends Controller
{
    public function slaMonitor()
    {
        $user = Auth::user();
        $role = $user->role;

        // Ambil 7 hari terakhir
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i));
        }

        $results = [];

        foreach ($dates as $dateObj) {

            $date = $dateObj->format('Y-m-d');
            $dayName = $dateObj->format('l');      // Full name: Sunday
            $dayShort = $dateObj->format('D');     // Short: Sun

            // Ticket resolved pada tanggal tersebut
            $tickets = Ticket::with(['assignees'])

                // FILTER ROLE
                ->when($role === 'user', function ($query) use ($user) {
                    $query->where('reporter_id', $user->id);
                })

                ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                    $query->whereHas('assignees', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                })

                ->whereDate('resolved_at', $date)
                ->get();

            // Hitung SLA
            $onTime = $tickets->filter(fn($t) => $t->resolved_at <= $t->deadline)->count();
            $breached = $tickets->filter(fn($t) => $t->resolved_at > $t->deadline)->count();

            $results[] = [
                'date' => $date,
                'day' => $dayShort, // gunakan "Sun, Mon, Tue..."
                'day_full' => $dayName, // gunakan jika ingin nama lengkap
                'onTime' => $onTime,
                'breached' => $breached
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }

    public function slaCompliance()
    {
        $user = Auth::user();
        $role = $user->role;

        // Ambil semua tiket yang sudah selesai
        $tickets = Ticket::with(['assignees'])
            ->whereNotNull('resolved_at')

            // FILTER USER
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })

            // FILTER teknisi, admin-bidang, admin-seksi
            ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                $query->whereHas('assignees', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })

            ->get();

        $doneBefore = 0;
        $approaching = 0;
        $breached = 0;

        foreach ($tickets as $t) {
            $deadline = \Carbon\Carbon::parse($t->deadline);
            $resolved = \Carbon\Carbon::parse($t->resolved_at);

            // Batas 2 jam sebelum deadline
            $twoHoursBefore = $deadline->copy()->subHours(2);

            if ($resolved > $deadline) {
                // Lewat deadline → BREACHED
                $breached++;
            } 
            else if ($resolved >= $twoHoursBefore && $resolved <= $deadline) {
                // Dalam 2 jam terakhir menuju deadline → APPROACHING
                $approaching++;
            } 
            else {
                // Lebih awal dari 2 jam → BEFORE DEADLINE
                $doneBefore++;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'withinSla' => $doneBefore,
                'nearBreach' => $approaching,
                'breached' => $breached,
                'average' => ($doneBefore + $approaching + $breached) / 3
            ]
        ]);
    }

    public function resollutionTrend()
    {
        $user = Auth::user();
        $role = $user->role;

        // Ambil 12 bulan terakhir (inklusive bulan ini)
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $results = [];

        foreach ($months as $monthObj) {

            $monthNumber = $monthObj->format('m');
            $yearNumber = $monthObj->format('Y');
            $monthName  = $monthObj->format('F'); // January, February, ...

            // Ambil ticket resolved dalam bulan ini
            $tickets = Ticket::with(['assignees'])

                // FILTER BERDASARKAN ROLE
                ->when($role === 'user', function ($query) use ($user) {
                    $query->where('reporter_id', $user->id);
                })

                ->when(in_array($role, ['admin-bidang', 'admin-seksi', 'teknisi']), function ($query) use ($user) {
                    $query->whereHas('assignees', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                })

                ->whereYear('resolved_at', $yearNumber)
                ->whereMonth('resolved_at', $monthNumber)
                ->get();

            // Hitung SLA
            $onTime = $tickets->filter(function ($t) {
                return $t->resolved_at <= $t->deadline;
            })->count();

            $breached = $tickets->filter(function ($t) {
                return $t->resolved_at > $t->deadline;
            })->count();

            $results[] = [
                'month'    => $monthName,        // January, February, ...
                'year'     => $yearNumber,
                'onTime'   => $onTime,
                'breached' => $breached
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }


}
