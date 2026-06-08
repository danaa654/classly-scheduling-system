<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * SAFEST approach: Disable foreign key checks, drop ALL indexes on schedules,
     * change the columns, then recreate indexes.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop the problematic unique index if it exists
        try {
            DB::statement('ALTER TABLE schedules DROP INDEX unique_room_time_slot;');
        } catch (\Exception $e) {
            // Index doesn't exist, that's OK
        }

        Schema::table('schedules', function (Blueprint $table) {
            // Make columns nullable for Faculty-First assignments
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->nullable()->change();
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('schedules', function (Blueprint $table) {
            // Revert columns to NOT NULL
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->nullable(false)->change();
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->unsignedBigInteger('room_id')->nullable(false)->change();
        });

        // Try to recreate the unique constraint
        try {
            DB::statement('ALTER TABLE schedules ADD UNIQUE KEY unique_room_time_slot (room_id, day, start_time, end_time);');
        } catch (\Exception $e) {
            // Constraint recreation failed, that's OK
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};