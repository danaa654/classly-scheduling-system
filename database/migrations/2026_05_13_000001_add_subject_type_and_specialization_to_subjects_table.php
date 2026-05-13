<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('type')->index();
            }

            if (!Schema::hasColumn('subjects', 'specialization')) {
                $table->string('specialization')->nullable()->after('subject_type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'specialization')) {
                $table->dropColumn('specialization');
            }

            if (Schema::hasColumn('subjects', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
        });
    }
};
