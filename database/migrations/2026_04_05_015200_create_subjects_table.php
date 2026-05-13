<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            // Core Identity
            $table->string('edp_code')->unique(); // e.g., QD-261001
            $table->string('subject_code');
            $table->string('section')->nullable(); 
            $table->string('description');
            
            // Classification
            $table->string('major');      
            $table->integer('year_level');
            $table->string('department')->nullable(); 
            
            // Subject Details
            $table->integer('units')->default(3);
            $table->decimal('duration_hours', 4, 2)->default(3.00);
            $table->enum('type', ['Major', 'Minor'])->default('Major');
            $table->string('subject_type')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('meetings_per_week')->default(1);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
