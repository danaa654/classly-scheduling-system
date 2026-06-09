<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // This replaces your manual tinker command perfectly!
        User::updateOrCreate(
            ['email' => 'admin@pap.edu.ph'], // Checks if this email already exists
            [
                'name' => 'System Admin',
                'password' => Hash::make('admin@pap.edu.ph123'), // Securely hashes your exact password
                'role' => 'admin',
                'department' => null,
            ]
        );
    }
}
