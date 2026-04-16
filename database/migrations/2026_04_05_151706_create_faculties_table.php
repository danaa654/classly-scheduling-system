<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use CREATE instead of TABLE
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
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};