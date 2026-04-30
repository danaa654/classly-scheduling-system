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
    Schema::create('subjects', function (Blueprint $table) {
        $table->id();
        $table->string('edp_code')->unique();
        $table->string('subject_code');
        $table->string('description');
        $table->integer('units')->default(3);
        $table->string('department')->nullable();
        $table->decimal('duration_hours', 4, 2);
        $table->string('type')->default('Major'); 
        $table->integer('meetings_per_week')->default(1);
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
