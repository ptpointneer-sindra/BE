<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin-opd',
            'admin-kota',
            'admin-bidang',
            'admin-seksi',
            'teknisi',
            'user'
        ];

        foreach ($roles as $roleName) {
            // Create role if not exists
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            // Create user for each role
            $user = User::firstOrCreate(
                ['email' => $roleName . '@gmail.com'],
                [
                    'role' => $roleName,
                    'name' => ucfirst(str_replace('-', ' ', $roleName)),
                    'password' => Hash::make('password'),
                    'email_verified_at' => Carbon::now(),
                ]
            );

            // Assign role ke user
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }
    }
}
