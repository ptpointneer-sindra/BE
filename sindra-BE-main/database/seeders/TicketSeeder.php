<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\TicketCategory;
use App\Models\Instansi;
use App\Models\TicketAsset;
use App\Models\TicketAssignee;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        $categoryIds = TicketCategory::pluck('id')->toArray();
        $instansiIds = Instansi::pluck('id')->toArray();
        $teknisiUsers = User::where('role', 'teknisi')->pluck('id')->toArray();
        $adminBidangUsers = User::where('role', 'admin-bidang')->pluck('id')->toArray();
        $adminSeksiUsers = User::where('role', 'admin-seksi')->pluck('id')->toArray();


        for ($i = 1; $i <= 100; $i++) {
            $randomDate = $this->randomDateBetweenTodayAnd7Days();

            $statusList = ['open','in_progress','resolved','closed'];
            $status = $statusList[array_rand($statusList)];
            
            $resolvedAt = null;
            if (in_array($status, ['resolved', 'closed'])) {
                $resolvedAt = Carbon::parse($randomDate)->addHours(3);
            }

           // Random chance type & priority bisa null
            $type     = rand(0, 1) ? ['incident','service_request'][array_rand(['incident','service_request'])] : null;
            $priority = rand(0, 1) ? ['low','medium','high','critical'][array_rand(['low','medium','high','critical'])] : 'low';

            // Jika type/priority null maka status harus open
            if ($type === null || $priority === null) {
                $status = 'open';
            }

            $resolvedAt = null;
            if (in_array($status, ['resolved', 'closed'])) {
                $resolvedAt = Carbon::parse($randomDate)->addHours(3);
            }

            $ticket = Ticket::create([
                'asset_uuid' => Str::uuid(),
                'reporter_id' => $users[array_rand($users)],
                'ticket_category_id' => $categoryIds[array_rand($categoryIds)],
                'instansi_id' => $instansiIds[array_rand($instansiIds)],
                'title' => "Laporan Kerusakan Barang #$i",
                'description' => "Deskripsi laporan kerusakan untuk barang ke-$i",
                'status' => $status,
                'priority' => $priority,
                'type'     => $type,
                'resolved_at' => $resolvedAt,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);

            TicketAsset::create([
                    'ticket_id' => $ticket->id,
                    'asset_code' => Str::uuid(),
                    'serial_number' => strtoupper(Str::random(8)),
                ]);

             // 1–2 teknisi
            $assignedTeknisi = $teknisiUsers;

            foreach ($assignedTeknisi as $uid) {
                TicketAssignee::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $uid,
                ]);
            }

            if ($ticket->type === 'incident') {
                $assignedRole = $adminBidangUsers; 
            } else { // service_request
                $assignedRole = $adminSeksiUsers;
            }

            foreach ($assignedRole as $uid) {
                TicketAssignee::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $uid,
                ]);
            }


            // Lampiran utama
            for ($j = 1; $j <= rand(1, 2); $j++) {
                DB::table('ticket_attachments')->insert([
                    'ticket_id' => $ticket->id,
                    'file_path' => "storage/tickets/file{$i}_{$j}.jpg",
                    'file_url' => url("/storage/tickets/file{$i}_{$j}.jpg"),
                    'file_name' => "lampiran_{$i}_{$j}.jpg",
                    'mime_type' => "image/jpeg",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $roleUsers = $ticket->type === 'incident' ? $adminBidangUsers : $adminSeksiUsers;

           // 🔹 Gabungkan semua peserta diskusi
            $participants = array_merge(
                [$ticket->reporter_id],
                $assignedTeknisi,
                $roleUsers
            );

            // 🔹 Generate diskusi (2–5x)
            for ($k = 1; $k <= rand(2, 5); $k++) {

                // Pilih random user dari participants
                $randomUserId = $participants[array_rand($participants)];

                $discussionId = DB::table('ticket_discussions')->insertGetId([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $randomUserId,
                    'message'    => fake()->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 🔹 Lampiran diskusi (optional)
                if (rand(0, 1)) {
                    DB::table('ticket_discussion_attachments')->insert([
                        'discussion_id' => $discussionId,
                        'file_path'     => "storage/ticket-discussions/file_{$discussionId}.png",
                        'file_name'     => "diskusi_{$discussionId}.png",
                        'mime_type'     => "image/png",
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }

            if (in_array($ticket->status, ['resolved', 'closed'])) {

                // Ambil semua assignee dari tiket ini
                $assignees = DB::table('ticket_assignees')
                    ->where('ticket_id', $ticket->id)
                    ->pluck('user_id')
                    ->toArray();

                foreach ($assignees as $userId) {
                    DB::table('ticket_feedbacks')->insert([
                        'ticket_id' => $ticket->id,
                        'user_id'   => $userId,  // <-- yang dinilai = assignee
                        'rating'    => rand(3, 5), // bisa dibikin realistis (lebih banyak 4–5)
                        'feedback'  => fake()->sentence(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($ticket->status === 'in_progress') {
                $destination = $ticket->type === 'incident' ? 'bidang' : 'seksi';

                DB::table('ticket_escalates')->insert([
                    'ticket_id' => $ticket->id,
                    'description' => 'Ticket sedang berlangsung dan membutuhkan eskalasi.',
                    'destination' => $destination,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        }
    }

    private function randomDateBetweenTodayAnd7Days()
    {
        $start = Carbon::today();
        $end   = Carbon::today()->addMonth();

        return Carbon::createFromTimestamp(
            rand($start->timestamp, $end->timestamp)
        );
    }

}
