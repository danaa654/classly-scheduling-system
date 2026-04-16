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
    Schema::table('faculties', function (Blueprint $table) {
        $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
    });
}

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            //
        });
    }
};
