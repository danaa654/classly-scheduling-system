<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
    Schema::create('faculty_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id'); // Who did the action
        $table->string('action');    // 'created', 'updated', 'deleted', 'rejected'
        $table->text('description');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_logs');
    }
};
