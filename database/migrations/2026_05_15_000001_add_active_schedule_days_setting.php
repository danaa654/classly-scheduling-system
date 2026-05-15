<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'active_days'],
                [
                    'value' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('schedules') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'active_days')->delete();
        }

        if (Schema::hasTable('schedules') && DB::getDriverName() === 'mysql') {
            $hasSundaySchedules = DB::table('schedules')->where('day', 'Sunday')->exists();

            if (!$hasSundaySchedules) {
                DB::statement("ALTER TABLE schedules MODIFY day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL");
            }
        }
    }
};
