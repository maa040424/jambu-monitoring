<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Buat akun admin & superadmin default.
     */
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@jambu.local'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'role'     => 'superadmin',
            ]
        );

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@jambu.local'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );
    }
}
