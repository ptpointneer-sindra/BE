<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Panduan Pengguna',
            'Kebijakan Privasi',
            'Teknologi & Tips',
            'FAQ',
            'Pengumuman'
        ];

        foreach ($categories as $name) {
            DB::table('categories')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
             
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
