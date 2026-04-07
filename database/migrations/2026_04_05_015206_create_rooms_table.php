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
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->string('room_name')->unique(); // e.g., Room 201
                $table->string('type')->default('Lecture'); // Lecture or Lab
                $table->integer('capacity')->default(40);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
