<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class AssigneeController extends Controller
{
    private function getUsersByRoleWithQuota($role)
    {
        $today = now()->startOfDay();

        $users = User::where('role', $role)
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
            ])
            ->get();

        $limitPerDay = 8;

        $mapped = $users->map(function ($user) use ($limitPerDay) {

            $count = $user->tickets_today ?? 0;

            // Hitung rata-rata rating
            $ratings = $user->feedbacks->pluck('rating');
            $avgRating = $ratings->count() > 0
                ? round($ratings->avg(), 2)
                : 0;

            // Tentukan status
            if ($count >= 7) {
                $status = 'not_available';
            } elseif ($count >= 4) {
                $status = 'busy';
            } else {
                $status = 'available';
            }

            return [
                'id'            => $user->id,
                'name'          => $user->name,
                'role'          => $user->role,
                'tickets_today' => $count,
                'quota'         => "{$count}/{$limitPerDay}",
                'remaining'     => max(0, $limitPerDay - $count),
                'rating_today'  => $avgRating,
                'status'        => $status,  // << 🟢 status ditambah di sini
            ];
        });

        return response()->json([
            'message' => "List {$role}",
            'data' => $mapped
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
