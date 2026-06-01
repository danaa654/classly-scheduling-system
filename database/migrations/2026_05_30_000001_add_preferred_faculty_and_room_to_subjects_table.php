<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'preferred_faculty_id')) {
                $after = Schema::hasColumn('subjects', 'faculty_id') ? 'faculty_id' : 'meetings_per_week';

                $table->foreignId('preferred_faculty_id')
                    ->nullable()
                    ->after($after)
                    ->constrained('faculties')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('subjects', 'preferred_room_id')) {
                $after = Schema::hasColumn('subjects', 'preferred_faculty_id')
                    ? 'preferred_faculty_id'
                    : 'preferred_room_type';

                $table->foreignId('preferred_room_id')
                    ->nullable()
                    ->after($after)
                    ->constrained('rooms')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'preferred_room_id')) {
                $table->dropConstrainedForeignId('preferred_room_id');
            }

            if (Schema::hasColumn('subjects', 'preferred_faculty_id')) {
                $table->dropConstrainedForeignId('preferred_faculty_id');
            }
        });
    }
};
