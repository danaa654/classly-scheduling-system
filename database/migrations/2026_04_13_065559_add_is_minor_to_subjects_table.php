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
    Schema::table('subjects', function (Blueprint $table) {
        $table->integer('duration_hours')->default(3)->after('type'); 
        $table->boolean('is_minor')->default(false); 
    });
}

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            //
        });
    }
};
