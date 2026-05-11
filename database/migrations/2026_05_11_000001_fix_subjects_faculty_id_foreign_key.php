<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'faculty_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('faculty_id')
                    ->nullable()
                    ->after('meetings_per_week');
            });
        }

        $this->dropForeignKeyOnFacultyId();

        DB::table('subjects')
            ->whereNotNull('faculty_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('faculties')
                    ->whereColumn('faculties.id', 'subjects.faculty_id');
            })
            ->update(['faculty_id' => null]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('faculty_id')->nullable()->change();
            $table->foreign('faculty_id')
                ->references('id')
                ->on('faculties')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignKeyOnFacultyId();

        DB::table('subjects')
            ->whereNotNull('faculty_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'subjects.faculty_id');
            })
            ->update(['faculty_id' => null]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('faculty_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function dropForeignKeyOnFacultyId(): void
    {
        $databaseName = DB::getDatabaseName();

        $constraintName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', 'subjects')
            ->where('COLUMN_NAME', 'faculty_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraintName) {
            DB::statement("ALTER TABLE `subjects` DROP FOREIGN KEY `{$constraintName}`");
        }
    }
};
