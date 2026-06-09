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
     * Make spacetime columns nullable to support Faculty-First placeholder assignments.
     * This migration:
     * 1. Drops the unique constraint
     * 2. Drops the foreign key on room_id
     * 3. Makes day, start_time, end_time, room_id nullable
     * 
     * NOTE: Foreign key is NOT recreated to avoid constraint conflicts.
     * The application will handle room_id validation.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Step 1: Drop the unique constraint if it exists
        try {
            DB::statement('ALTER TABLE schedules DROP INDEX unique_room_time_slot;');
        } catch (\Exception $e) {
            // Index doesn't exist, that's OK
        }

        // Step 2: Drop foreign key constraint on room_id
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'schedules' 
                AND COLUMN_NAME = 'room_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($constraints)) {
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE schedules DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`;");
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Could not drop room_id foreign key: ' . $e->getMessage());
        }

        // Step 3: Make columns nullable
        try {
            Schema::table('schedules', function (Blueprint $table) {
                $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->nullable()->change();
                $table->time('start_time')->nullable()->change();
                $table->time('end_time')->nullable()->change();
                $table->unsignedBigInteger('room_id')->nullable()->change();
            });
        } catch (\Exception $e) {
            \Log::warning('Could not make columns nullable: ' . $e->getMessage());
            throw $e;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     * 
     * Simply removes the nullability - this is a one-way migration.
     * If you need to revert, manually restore the foreign key constraint.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete any rows with NULL spacetime values
        DB::table('schedules')
            ->where(function ($query) {
                $query->whereNull('room_id')
                    ->orWhereNull('day')
                    ->orWhereNull('start_time')
                    ->orWhereNull('end_time');
            })
            ->delete();

        // Revert columns to NOT NULL
        try {
            Schema::table('schedules', function (Blueprint $table) {
                $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->change();
                $table->time('start_time')->change();
                $table->time('end_time')->change();
                $table->unsignedBigInteger('room_id')->change();
            });
        } catch (\Exception $e) {
            \Log::warning('Could not revert columns to NOT NULL: ' . $e->getMessage());
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};