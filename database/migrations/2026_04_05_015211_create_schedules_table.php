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
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->foreignId('faculty_id')->constrained('users'); // Faculty are users
            $table->foreignId('created_by')->constrained('users'); // Registrar/Dean
            $table->string('day'); // e.g., M-W-F or T-TH
            $table->time('start_time');
            $table->time('end_time');
            $table->string('semester');
            $table->string('academic_year');
            $table->enum('status', ['draft', 'finalized'])->default('draft');
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
