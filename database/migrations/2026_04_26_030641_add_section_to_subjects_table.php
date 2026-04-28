<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::table('subjects', function (Blueprint $table) {
        // This adds the column without deleting your existing data
        $table->string('section')->nullable()->after('subject_code');
    });
}

public function down(): void
{
    Schema::table('subjects', function (Blueprint $table) {
        $table->dropColumn('section');
    });
}
};
