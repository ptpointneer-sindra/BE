<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketCategory;
use Illuminate\Support\Str;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Software Issue',
            'Hardware Issue',
            'Network Issue',
            'Account & Access',
            'General Inquiry'
        ];

        foreach ($categories as $name) {
            TicketCategory::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => true, // aktif
            ]);
        }
    }
}
