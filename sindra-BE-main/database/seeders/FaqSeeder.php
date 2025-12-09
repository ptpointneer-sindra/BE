<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Bagaimana cara membuat akun?',
                'answer' => 'Klik tombol "Daftar" di halaman utama dan isi data yang diperlukan untuk membuat akun baru.',
            ],
            [
                'question' => 'Apakah layanan ini gratis?',
                'answer' => 'Sebagian besar fitur kami gratis. Namun, ada fitur premium yang memerlukan langganan berbayar.',
            ],
            [
                'question' => 'Bagaimana cara mengubah kata sandi?',
                'answer' => 'Masuk ke menu "Profil" lalu pilih "Ubah Kata Sandi". Masukkan kata sandi lama dan baru Anda.',
            ],
            [
                'question' => 'Apakah data saya aman?',
                'answer' => 'Kami menggunakan enkripsi dan sistem keamanan modern untuk melindungi data pengguna.',
            ],
            [
                'question' => 'Bagaimana cara menghubungi dukungan pelanggan?',
                'answer' => 'Anda bisa mengirim pesan melalui halaman "Kontak Kami" atau email ke support@example.com.',
            ],
        ];

        foreach ($faqs as $faq) {
            DB::table('faqs')->insert([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
