<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates enhanced settings infrastructure with audit trails,
     * access control, and operational boundaries for PAP compliance.
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
            $table->boolean('is_locked')->default(true)->comment('Prevents accidental changes to configuration');
            $table->unsignedBigInteger('last_updated_by')->nullable()->comment('User ID of last modifier');
            $table->timestamp('last_updated_at')->nullable()->comment('When the setting was last changed');
            
            // ========== OPERATIONAL BOUNDARIES ==========
            $table->time('day_start_time')->default('07:00')->comment('Earliest class can be scheduled');
            $table->time('day_end_time')->default('20:00')->comment('Latest class can end');
            $table->integer('default_slot_duration')->default(60)->comment('Default duration in minutes (60, 90, 120)');
            $table->time('lunch_break_start')->default('12:00')->comment('Lunch break start time');
            $table->time('lunch_break_end')->default('13:00')->comment('Lunch break end time');
            $table->string('active_semester')->default('First Semester 2026-2027')->comment('Current academic semester');
            
            // ========== ROOM CONSTRAINTS ==========
            $table->integer('turnover_buffer')->default(10)->comment('Minutes between classes for room setup (0-30)');
            $table->integer('default_room_capacity')->default(40)->comment('Default room capacity (10-200)');
            $table->string('room_type_lecture')->default('Lecture Room')->comment('Display name for lecture rooms');
            $table->string('room_type_lab')->default('Laboratory')->comment('Display name for lab rooms');
            
            // ========== INSTITUTIONAL STATE ==========
            $table->boolean('maintenance_mode')->default(false)->comment('Prevents Deans from modifying subjects');
            $table->boolean('scheduling_locked')->default(false)->comment('Prevents all scheduling during maintenance');
            $table->string('institution_name')->nullable()->comment('College/University name for headers');
            $table->string('institution_logo_url')->nullable()->comment('URL to institutional logo');
            $table->string('academic_calendar_year')->default('2026-2027')->comment('Academic year for reporting');
            
            // ========== CONFLICT DETECTION FLAGS ==========
            $table->boolean('strict_room_conflict')->default(true)->comment('Enforce strict room conflict detection');
            $table->boolean('strict_faculty_conflict')->default(true)->comment('Prevent faculty double-booking');
            $table->boolean('allow_overlapping_sections')->default(false)->comment('Allow same subject/section in different rooms');
            
            // ========== REPORTING & ANALYTICS ==========
            $table->boolean('enable_utilization_tracking')->default(true)->comment('Track room utilization metrics');
            $table->integer('max_classes_per_faculty')->default(5)->comment('Maximum classes per faculty member');
            $table->integer('max_daily_hours_per_faculty')->default(8)->comment('Maximum hours per faculty per day');
            
            $table->timestamps();
            
            // FOREIGN KEY REFERENCE
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
            $table->text('old_value')->nullable()->comment('Previous value before change');
            $table->text('new_value')->nullable()->comment('New value after change');
            $table->enum('action', ['created', 'updated', 'deleted', 'locked', 'unlocked', 'reset'])->default('updated');
            $table->string('ip_address')->nullable()->comment('IP address of user making change');
            $table->text('change_reason')->nullable()->comment('Optional reason for change');
            
            // TIMESTAMPS
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
            
            // INDEXES FOR FAST QUERIES
            $table->index(['user_id', 'changed_at']);
            $table->index(['setting_key', 'changed_at']);
            
            // FOREIGN KEYS
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
            $table->string('semester_name')->index()->comment('Academic semester identifier');
            $table->string('academic_year')->comment('Year of the semester (e.g., 2026-2027)');
            $table->integer('total_schedules')->default(0)->comment('Count of archived schedules');
            
            // ARCHIVE DATA
            $table->longText('schedule_data')->comment('JSON snapshot of all schedules');
            $table->longText('metadata')->nullable()->comment('Additional metadata: room count, faculty count, etc.');
            
            // ARCHIVE CONTEXT
            $table->unsignedBigInteger('archived_by')->comment('User ID who triggered archive');
            $table->text('archive_notes')->nullable()->comment('Notes about the archive (e.g., end of semester reason)');
            $table->boolean('is_locked')->default(true)->comment('Prevent modification of archived data');
            
            // RESTORATION CAPABILITY
            $table->dateTime('archived_at')->comment('When archive was created');
            $table->dateTime('can_restore_until')->nullable()->comment('Deadline for restore option (30 days)');
            $table->boolean('is_restored')->default(false)->comment('Whether this archive was restored');
            $table->dateTime('restored_at')->nullable()->comment('If restored, when it was restored');
            
            $table->timestamps();
            
            // INDEXES
            $table->index(['semester_name', 'academic_year']);
            $table->index('archived_at');
            
            // FOREIGN KEY
            $table->foreign('archived_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // ============================================================
        // TABLE 4: SYSTEM CONFIGURATION SNAPSHOTS
        // ============================================================
        Schema::create('setting_snapshots', function (Blueprint $table) {
            $table->id();
            
            // SNAPSHOT METADATA
            $table->string('snapshot_name')->comment('Descriptive name (e.g., "Pre-Semester-Setup")');
            $table->text('description')->nullable()->comment('Purpose of snapshot');
            $table->string('status')->default('active')->comment('active, archived, deleted');
            
            // SNAPSHOT DATA
            $table->longText('settings_data')->comment('JSON of all settings at snapshot time');
            
            // CONTEXT
            $table->unsignedBigInteger('created_by')->comment('User who created snapshot');
            $table->timestamp('snapshot_at')->useCurrent()->comment('When snapshot was taken');
            $table->boolean('is_automatic')->default(false)->comment('Auto-generated or manual');
            
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

        // ============================================================
        // TABLE 5: CONFIGURATION ROLLBACK HISTORY
        // ============================================================
        Schema::create('setting_rollbacks', function (Blueprint $table) {
            $table->id();
            
            // ROLLBACK METADATA
            $table->unsignedBigInteger('initiated_by')->comment('User who triggered rollback');
            $table->string('rollback_reason')->comment('Why rollback was needed');
            $table->timestamp('rolled_back_to')->comment('Settings reverted to this point in time');
            
            // SNAPSHOT REFERENCE
            $table->unsignedBigInteger('snapshot_id')->nullable();
            
            // RESULT
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable()->comment('If failed, error details');
            $table->integer('settings_affected')->default(0)->comment('Number of settings changed');
            
            $table->timestamps();
            
            // INDEXES
            $table->index('status');
            $table->index(['initiated_by', 'created_at']);
            
            // FOREIGN KEYS
            $table->foreign('initiated_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            $table->foreign('snapshot_id')
                ->references('id')
                ->on('setting_snapshots')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_rollbacks');
        Schema::dropIfExists('setting_snapshots');
        Schema::dropIfExists('schedule_archives');
        Schema::dropIfExists('setting_change_logs');
        Schema::dropIfExists('settings');
    }
};