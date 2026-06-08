<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
        $table->string('section', 10)->default('A');
        
        // These should be NULLABLE for Faculty-First
        $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->nullable(); // ← ADD nullable()
        $table->time('start_time')->nullable(); // ← ADD nullable()
        $table->time('end_time')->nullable(); // ← ADD nullable()
        $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null'); // ← ADD nullable()
        
        // Rest of columns...
        $table->string('section', 10)->default('A');
        $table->enum('status', ['draft', 'partial', 'faculty_assigned', 'faculty_locked', 'pending_generation', 'finalized'])->default('draft');
        $table->string('semester')->nullable();
        $table->string('school_year')->nullable();
        $table->string('academic_year')->nullable();
        $table->string('workspace_key')->nullable();
        $table->string('edp_code')->nullable();
        
        // Indexes
        $table->index(['room_id', 'day']);
        $table->index(['subject_id', 'day']);
        $table->index(['user_id', 'day']);
        $table->unique(['room_id', 'day', 'start_time', 'end_time'], 'unique_room_time_slot');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
