<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedule_archives')) {
            return;
        }

        Schema::table('schedule_archives', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_archives', 'archive_batch_id')) {
                $table->string('archive_batch_id', 80)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('schedule_archives', 'semester')) {
                $table->string('semester', 50)->nullable()->after('archive_batch_id');
            }

            if (! Schema::hasColumn('schedule_archives', 'total_subjects')) {
                $table->integer('total_subjects')->default(0)->after('total_schedules');
            }

            if (! Schema::hasColumn('schedule_archives', 'next_semester')) {
                $table->string('next_semester', 50)->nullable()->after('total_subjects');
            }

            if (! Schema::hasColumn('schedule_archives', 'next_school_year')) {
                $table->string('next_school_year', 20)->nullable()->after('next_semester');
            }

            if (! Schema::hasIndex('schedule_archives', 'idx_schedule_archives_term')) {
                $table->index(['semester', 'school_year'], 'idx_schedule_archives_term');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedule_archives')) {
            return;
        }

        Schema::table('schedule_archives', function (Blueprint $table) {
            if (Schema::hasIndex('schedule_archives', 'idx_schedule_archives_term')) {
                $table->dropIndex('idx_schedule_archives_term');
            }

            $columns = collect([
                'archive_batch_id',
                'semester',
                'total_subjects',
                'next_semester',
                'next_school_year',
            ])->filter(fn (string $column) => Schema::hasColumn('schedule_archives', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
