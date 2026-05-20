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
            'full_name' => 'Jemer Paul Agujetas',
            'employee_id' => 'FAC-2026-001',
            'department' => 'CCS',
            'faculty_scope' => Faculty::SCOPE_DEPARTMENTAL,
            'can_teach_minor' => false,
        ]);

        // Additional Team Members for testing the Grid
        Faculty::create([
            'full_name' => 'Eurel Panilag',
            'employee_id' => 'FAC-2026-002',
            'department' => 'CCS',
            'faculty_scope' => Faculty::SCOPE_DEPARTMENTAL,
            'can_teach_minor' => true,
        ]);

        Faculty::create([
            'full_name' => 'Trixie Tinapao',
            'employee_id' => 'FAC-2026-003',
            'department' => 'CCS',
            'faculty_scope' => Faculty::SCOPE_CROSS_DEPARTMENT,
            'can_teach_minor' => true,
        ]);

        Faculty::create([
            'full_name' => 'Mitch Lombreno',
            'employee_id' => 'FAC-2026-004',
            'department' => null,
            'faculty_scope' => Faculty::SCOPE_GENED,
            'can_teach_minor' => true,
        ]);
    }
}
