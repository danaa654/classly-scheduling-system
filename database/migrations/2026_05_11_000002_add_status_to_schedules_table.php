<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('schedules', 'status')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->string('status')->default('partial')->after('end_time')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schedules', 'status')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
