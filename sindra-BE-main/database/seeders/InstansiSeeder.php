<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Instansi;

class InstansiSeeder extends Seeder
{
    public function run(): void
    {
        $instansis = [
            'Dinas Pendidikan',
            'Dinas Kesehatan',
            'Dinas Perhubungan',
            'Dinas Sosial',
            'Dinas Tenaga Kerja',
        ];

        foreach ($instansis as $name) {
            Instansi::create([
                'name' => $name,
                'status' => true, // aktif
            ]);
        }
    }
}
