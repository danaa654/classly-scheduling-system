<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primary Test Entry
        Faculty::create([
        'full_name' => 'Simon Demo',
        'employee_id' => 'FAC-2026-001',
        'faculty_id' => 1, // Or whatever value your database is looking for!
        'department' => 'CCS',
        'faculty_scope' => Faculty::SCOPE_DEPARTMENTAL,
        'can_teach_minor' => false,
        ]);

        // Additional Team Members for testing the Grid
        Faculty::create([
            'full_name' => 'Rasmus Demo',
            'employee_id' => 'FAC-2026-002',
            'faculty_id' => 2,
            'department' => 'CCS',
            'faculty_scope' => Faculty::SCOPE_DEPARTMENTAL,
            'can_teach_minor' => true,
        ]);

    }
}
