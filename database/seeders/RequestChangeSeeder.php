<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RequestChangeSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada user dan ticket terlebih dahulu
        $userIds = DB::table('users')->pluck('id')->toArray();
        $ticketIds = DB::table('tickets')->pluck('id')->toArray();

        if (empty($userIds) || empty($ticketIds)) {
            dump("Seeder gagal: Pastikan tabel users dan tickets punya data.");
            return;
        }

        $statuses = ['pending'];

        for ($i = 1; $i <= 10; $i++) {

            $status = $statuses[array_rand($statuses)];

            $requestChangeId = DB::table('request_changes')->insertGetId([
                'ticket_id'     => $ticketIds[array_rand($ticketIds)],
                'reporter_id'   => $userIds[array_rand($userIds)],
                'asset_uuid'    => Str::uuid(),
                'description'   => 'Dummy description for request change #' . $i,
                'status'        => $status,
                'config_comment'=> $status !== 'pending' ? 'Komentar config untuk status '.$status : null,
                'requested_at'  => Carbon::now()->subDays(rand(1, 10)),
                'reviewed_at'   => $status !== 'pending' ? Carbon::now()->subDays(rand(0, 5)) : null,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]);

            // === Insert Attachments (1–3 file) ===
            $attachmentCount = rand(1, 3);

            for ($j = 1; $j <= $attachmentCount; $j++) {
                DB::table('request_change_attachments')->insert([
                    'request_change_id' => $requestChangeId,
                    'file_path'         => 'uploads/dummy/file_'.$i.'_'.$j.'.jpg',
                    'created_at'        => Carbon::now(),
                    'updated_at'        => Carbon::now(),
                ]);
            }
        }
    }
}
