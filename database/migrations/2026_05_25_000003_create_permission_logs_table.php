<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `permission_logs` table for auditing admin permission toggles.
 *
 * Tracks:
 *  - Who granted/revoked Registrar finalization access
 *  - Who finalized a schedule (and which block)
 *  - Timestamps for all actions
 *
 * Usage:
 *   PermissionLog::record('grant_registrar_finalize', auth()->user(), [...context])
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_logs', function (Blueprint $table) {
            $table->id();

            // The action performed — see PermissionLog::ACTION_* constants
            $table->string('action');

            // The user who performed the action (Admin granting/revoking, Registrar finalizing)
            $table->foreignId('performed_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('User who triggered the action.');

            // Optional: the user the action was performed ON (e.g., registrar receiving permission)
            $table->foreignId('target_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('User the action targeted, e.g., the Registrar getting permission.');

            // Freeform JSON context: department, year, section, semester, school_year, note
            $table->json('context')->nullable()
                  ->comment('Structured context: department, year_level, section, semester, etc.');

            // Human-readable description for quick log reading
            $table->string('description')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('action');
            $table->index('performed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_logs');
    }
};