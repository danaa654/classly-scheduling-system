<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update status column to support new values
        DB::statement("ALTER TABLE schedules MODIFY COLUMN status ENUM('draft', 'partial', 'faculty_assigned', 'faculty_locked', 'pending_generation', 'finalized')");
        
        // Convert existing schedules with NULL spacetime to faculty_locked
        DB::statement("
            UPDATE schedules 
            SET status = 'faculty_locked'
            WHERE status = 'faculty_assigned'
            AND (day IS NULL OR start_time IS NULL OR end_time IS NULL OR room_id IS NULL)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('schedules', 'status')) {
            return;
        }

        // Revert status changes
        DB::statement("
            UPDATE schedules 
            SET status = 'faculty_assigned'
            WHERE status = 'faculty_locked'
        ");
        
        // Revert column type
        DB::statement("ALTER TABLE schedules MODIFY COLUMN status ENUM('draft', 'partial', 'faculty_assigned', 'finalized')");
    }
};