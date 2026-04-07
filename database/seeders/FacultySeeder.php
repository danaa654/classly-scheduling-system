<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faculty;

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
            'department' => 'College of Computer Studies',
        ]);

        // Additional Team Members for testing the Grid
        Faculty::create([
            'full_name' => 'Eurel Panilag',
            'employee_id' => 'FAC-2026-002',
            'department' => 'College of Computer Studies',
        ]);

        Faculty::create([
            'full_name' => 'Trixie Tinapao',
            'employee_id' => 'FAC-2026-003',
            'department' => 'College of Computer Studies',
        ]);
        
        Faculty::create([
            'full_name' => 'Mitch Lombreno',
            'employee_id' => 'FAC-2026-004',
            'department' => 'College of Computer Studies',
        ]);
    }
}