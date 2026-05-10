<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            
            // Status and Tracking
            $table->string('status')->default('approved'); 
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();

            // Faculty Details
            $table->string('employee_id')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('department');
            
            // Scheduling-Related Fields
            $table->string('employment_type')->default('Full-time');
            $table->string('teaching_specialization')->default('Both');
            $table->integer('max_units')->default(21);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};
