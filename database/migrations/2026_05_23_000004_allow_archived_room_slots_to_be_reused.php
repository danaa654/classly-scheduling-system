<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'unique_room_time_slot')) {
                $table->dropUnique('unique_room_time_slot');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_room_time_term_archive')) {
                $table->index(
                    ['room_id', 'day', 'start_time', 'end_time', 'semester', 'academic_year', 'is_archived'],
                    'idx_schedules_room_time_term_archive'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'idx_schedules_room_time_term_archive')) {
                $table->dropIndex('idx_schedules_room_time_term_archive');
            }
        });

        $hasDuplicateRoomSlots = DB::table('schedules')
            ->select('room_id', 'day', 'start_time', 'end_time')
            ->groupBy('room_id', 'day', 'start_time', 'end_time')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateRoomSlots && ! Schema::hasIndex('schedules', 'unique_room_time_slot')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->unique(['room_id', 'day', 'start_time', 'end_time'], 'unique_room_time_slot');
            });
        }
    }
};
