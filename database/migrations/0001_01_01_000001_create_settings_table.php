<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates settings infrastructure with audit trails and operational boundaries.
     * REFACTORED: Removed department management, hard-coded lunch break (12:00-13:00).
     * Added school_year and semester fields.
     */
    public function up(): void
    {
        // ============================================================
        // TABLE 1: SETTINGS (Global Configuration)
        // ============================================================
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            
            // PRIMARY KEY
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            
            // ========== ACCESS CONTROL & AUDIT TRAIL ==========
            $table->boolean('config_locked')->default(true)->comment('Prevents changes to configuration');
            $table->unsignedBigInteger('last_updated_by')->nullable()->comment('User ID of last modifier');
            $table->timestamp('last_updated_at')->nullable()->comment('When the setting was last changed');
            
            // ========== OPERATIONAL BOUNDARIES (Master Bounds) ==========
            $table->time('day_start')->default('07:00')->comment('Earliest class can be scheduled');
            $table->time('day_end')->default('21:00')->comment('Latest class can end');
            
            // ========== HARD-CODED LUNCH BREAK ==========
            $table->time('lunch_start')->default('12:00')->comment('Fixed lunch break start - DO NOT EDIT');
            $table->time('lunch_end')->default('13:00')->comment('Fixed lunch break end - DO NOT EDIT');
            
            // ========== ACADEMIC CONFIGURATION ==========
            $table->string('school_year')->default('2026-2027')->comment('Academic year (e.g., 2026-2027)');
            $table->string('semester')->default('1st Semester')->comment('Semester (1st, 2nd, Summer)');
            $table->string('semester_name')->nullable()->comment('Display name for current semester');
            
            // ========== SYSTEM STATE ==========
            $table->boolean('maintenance_mode')->default(false)->comment('Prevents Deans from modifying subjects');
            $table->boolean('scheduling_locked')->default(false)->comment('Prevents all scheduling during maintenance');
            $table->string('institution_name')->nullable()->comment('College/University name');
            $table->string('institution_logo_url')->nullable()->comment('URL to institutional logo');
            
            // ========== CONFLICT DETECTION ==========
            $table->boolean('strict_room_conflict')->default(true)->comment('Enforce strict room conflict detection');
            $table->boolean('strict_faculty_conflict')->default(true)->comment('Prevent faculty double-booking');
            $table->boolean('allow_overlapping_sections')->default(false)->comment('Allow same subject/section in different rooms');
            
            // ========== REPORTING & ANALYTICS ==========
            $table->boolean('enable_utilization_tracking')->default(true)->comment('Track room utilization metrics');
            $table->integer('max_classes_per_faculty')->default(5)->comment('Maximum classes per faculty');
            $table->integer('max_daily_hours_per_faculty')->default(8)->comment('Maximum hours per faculty per day');
            
            // ========== SYSTEM CONFIGURATION ==========
            $table->integer('default_slot_duration')->default(30)->comment('Duration in minutes (30 min brick)');
            $table->integer('turnover_buffer')->default(0)->comment('Minutes between classes (0-30)');
            $table->integer('default_room_capacity')->default(40)->comment('Default room capacity (10-200)');
            
            $table->timestamps();
            
            // FOREIGN KEY
            $table->foreign('last_updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // ============================================================
        // TABLE 2: SETTING CHANGE LOG (Audit Trail)
        // ============================================================
        Schema::create('setting_change_logs', function (Blueprint $table) {
            $table->id();
            
            // AUDIT METADATA
            $table->unsignedBigInteger('user_id');
            $table->string('setting_key')->index();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->enum('action', ['created', 'updated', 'deleted', 'locked', 'unlocked', 'reset'])->default('updated');
            $table->string('ip_address')->nullable();
            $table->text('change_reason')->nullable();
            
            // TIMESTAMPS
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
            
            // INDEXES
            $table->index(['user_id', 'changed_at']);
            $table->index(['setting_key', 'changed_at']);
            
            // FOREIGN KEY
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // ============================================================
        // TABLE 3: SCHEDULE ARCHIVES (Historical Data)
        // ============================================================
        Schema::create('schedule_archives', function (Blueprint $table) {
            $table->id();
            
            // ARCHIVE METADATA
            $table->string('semester_name')->index();
            $table->string('school_year');
            $table->integer('total_schedules')->default(0);
            
            // ARCHIVE DATA
            $table->longText('schedule_data')->comment('JSON snapshot of all schedules');
            $table->longText('metadata')->nullable();
            
            // ARCHIVE CONTEXT
            $table->unsignedBigInteger('archived_by');
            $table->text('archive_notes')->nullable();
            $table->boolean('is_locked')->default(true);
            
            // RESTORATION CAPABILITY
            $table->dateTime('archived_at');
            $table->dateTime('can_restore_until')->nullable();
            $table->boolean('is_restored')->default(false);
            $table->dateTime('restored_at')->nullable();
            
            $table->timestamps();
            
            // INDEXES
            $table->index(['semester_name', 'school_year']);
            $table->index('archived_at');
            
            // FOREIGN KEY
            $table->foreign('archived_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // ============================================================
        // TABLE 4: CONFIGURATION SNAPSHOTS
        // ============================================================
        Schema::create('setting_snapshots', function (Blueprint $table) {
            $table->id();
            
            // SNAPSHOT METADATA
            $table->string('snapshot_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            
            // SNAPSHOT DATA
            $table->longText('settings_data')->comment('JSON of all settings at snapshot time');
            
            // CONTEXT
            $table->unsignedBigInteger('created_by');
            $table->timestamp('snapshot_at')->useCurrent();
            $table->boolean('is_automatic')->default(false);
            
            $table->timestamps();
            
            // INDEXES
            $table->index('status');
            $table->index('snapshot_at');
            
            // FOREIGN KEY
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        DB::table('settings')->insert([
            [
                'key' => 'active_days',
                'value' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'day_start',
                'value' => '07:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'day_end',
                'value' => '21:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_year',
                'value' => '2026-2027',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'semester',
                'value' => '1st',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'semester_name',
                'value' => 'First Semester 2026-2027',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_locked',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_slot_duration',
                'value' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_snapshots');
        Schema::dropIfExists('schedule_archives');
        Schema::dropIfExists('setting_change_logs');
        Schema::dropIfExists('settings');
    }
};
