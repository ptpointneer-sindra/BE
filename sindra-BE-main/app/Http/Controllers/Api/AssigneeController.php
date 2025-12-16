<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssigneeController extends Controller
{
    private function getUsersByRoleWithQuota($role)
    {
        $today = now()->startOfDay();
        $limitPerDay = 8;
        $query = User::where('role', $role)
            ->withCount([
                'ticketAssignees as tickets_today' => function ($q) use ($today) {
                    $q->where('created_at', '>=', $today);
                },
            ])
            ->with([
                'feedbacks' => function ($q) use ($today) {
                    $q->where('created_at', '>=', $today)
                        ->select('id', 'user_id', 'rating');
                }
            ]);
        if (request()->has('paginate')) {

            $perPage = request('per_page', 10);
            $users = $query->paginate($perPage);
            $mapped = $users->getCollection()->map(function ($user) use ($limitPerDay) {

                $count = $user->tickets_today ?? 0;

                $ratings = $user->feedbacks->pluck('rating');
                $avgRating = $ratings->count() > 0 ? round($ratings->avg(), 2) : 0;

                if ($count >= 7) $status = 'not_available';
                elseif ($count >= 4) $status = 'busy';
                else $status = 'available';

                return [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'role'          => $user->role,
                    'tickets_today' => $count,
                    'quota'         => "{$count}/{$limitPerDay}",
                    'remaining'     => max(0, $limitPerDay - $count),
                    'rating_today'  => $avgRating,
                    'status'        => $status,
                ];
            });
            $users->setCollection($mapped);

            return response()->json([
                'message' => "List {$role}",
                'data' => $users
            ]);
        }
        $users = $query->get();

        $mapped = $users->map(function ($user) use ($limitPerDay) {

            $count = $user->tickets_today ?? 0;

            $ratings = $user->feedbacks->pluck('rating');
            $avgRating = $ratings->count() > 0 ? round($ratings->avg(), 2) : 0;

            if ($count >= 7) $status = 'not_available';
            elseif ($count >= 4) $status = 'busy';
            else $status = 'available';

            return [
                'id'            => $user->id,
                'name'          => $user->name,
                'role'          => $user->role,
                'tickets_today' => $count,
                'quota'         => "{$count}/{$limitPerDay}",
                'remaining'     => max(0, $limitPerDay - $count),
                'rating_today'  => $avgRating,
                'status'        => $status,
            ];
        });

        return response()->json([
            'message' => "List {$role}",
            'data' => $mapped
        ]);
    }

    public function countTeknisi()
    {
        // Total teknisi
        $countTeknisi = User::where('role', 'teknisi')->count();

        // Teknisi on duty berdasarkan tickets.deadline hari ini
        $onDuty = User::where('role', 'teknisi')
            ->whereIn('id', function($query) {
                $query->select('ticket_assignees.user_id')
                    ->from('ticket_assignees')
                    ->join('tickets', 'tickets.id', '=', 'ticket_assignees.ticket_id')
                    ->whereDate('tickets.deadline_at', today()); // filter HARI INI
            })
            ->distinct()
            ->count();


        $countResolved = Ticket::where('status','resolved')->count();

         // Hitung rata-rata response time (resolved_at - created_at)
        $avgResponse = Ticket::where('status', 'resolved')
        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
        ->value('avg_minutes');
            
        $customerSatisfaction = DB::table('ticket_feedbacks')
        ->avg('rating');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $countTeknisi,
                'active' => $countTeknisi,
                'onDuty' => $onDuty,
                'status' => [
                    'available' => $countTeknisi,
                    'busy' => $onDuty,
                    'offline' => 0,
                ],
                'performance' => [
                    'ticketResolved' => $countResolved,
                    'avgResponse' => $avgResponse ? round($avgResponse, 2) : 0,
                    'customerSatisfaction' => $customerSatisfaction
                ]
            ]
        ]);
    }


    public function getTeknisi()
    {
        return $this->getUsersByRoleWithQuota('teknisi');
    }

    public function getAdminBidang()
    {
        return $this->getUsersByRoleWithQuota('admin-bidang');
    }

    public function getAdminSeksi()
    {
        return $this->getUsersByRoleWithQuota('admin-seksi');
    }
}
