<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Admin
        User::updateOrCreate(
            ['email' => 'admin@pap.edu.ph'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(), // CRITICAL: Fixes 403 error
            ]
        );

        // 2. Create Registrar
        User::updateOrCreate(
            ['email' => 'registrar@pap.edu.ph'],
            [
                'name' => 'Registrar Office',
                'password' => Hash::make('password123'),
                'role' => 'registrar',
                'email_verified_at' => now(),
            ]
        );

        // 3. Create CCS Dean (For your CCS filtering test)
        User::updateOrCreate(
            ['email' => 'dean.ccs@pap.edu.ph'],
            [
                'name' => 'Diodana Jane Sedemo',
                'password' => Hash::make('password123'),
                'role' => 'dean',
                'department' => 'CCS', // Matches your MasterGrid filtering
                'email_verified_at' => now(),
            ]
        );
    }
}