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
       Schema::table('schedules', function (Blueprint $table) {
           // Adds the missing user_id column right after the status column
           $table->foreignId('user_id')->nullable()->after('status')->constrained()->onDelete('cascade');
       });
   }

   public function down(): void
   {
       Schema::table('schedules', function (Blueprint $table) {
           $table->dropForeign(['user_id']);
           $table->dropColumn('user_id');
       });
   }
};
