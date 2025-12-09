<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua kategori
        $categories = DB::table('categories')->pluck('id', 'name');

        // Data contoh Knowledge Base
        $data = [
            [
                'title' => 'Cara Menggunakan Platform Kami',
                'category' => 'Panduan Pengguna',
                'content' => 'Panduan lengkap untuk memulai menggunakan platform kami, mulai dari registrasi hingga pengaturan profil.',
            ],
            [
                'title' => 'Kebijakan Privasi dan Perlindungan Data',
                'category' => 'Kebijakan Privasi',
                'content' => 'Kami berkomitmen melindungi data pribadi pengguna. Baca kebijakan privasi kami di sini.',
            ],
            [
                'title' => 'Tips Memaksimalkan Fitur Aplikasi',
                'category' => 'Teknologi & Tips',
                'content' => 'Pelajari bagaimana menggunakan fitur-fitur utama untuk meningkatkan produktivitas Anda.',
            ],
            [
                'title' => 'Pertanyaan Umum (FAQ)',
                'category' => 'FAQ',
                'content' => 'Kumpulan pertanyaan umum yang sering diajukan oleh pengguna baru maupun lama.',
            ],
            [
                'title' => 'Pengumuman Sistem Pemeliharaan',
                'category' => 'Pengumuman',
                'content' => 'Kami akan melakukan pemeliharaan sistem pada tanggal 20 Oktober 2025 pukul 00:00 WIB.',
            ],
        ];

        foreach ($data as $item) {
            DB::table('knowledge_bases')->insert([
                'author_id' => 1, // sesuaikan dengan ID user yang ada
                'category_id' => $categories[$item['category']] ?? null,
                'title' => $item['title'],
                'slug' => Str::slug($item['title']),
                'content' => $item['content'],
                'image_url' => 'https://placehold.co/600x400?text=Knowledge+Base',
                'is_published' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
