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
        
        // Just place this line after department. 
        // No 'after' keyword needed during table creation.
        $table->string('type')->default('Major'); 
        
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
