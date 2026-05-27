<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('requested_faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->json('schedule_ids')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->string('workspace_key')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'status']);
            $table->index(['semester', 'school_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_revision_requests');
    }
};
