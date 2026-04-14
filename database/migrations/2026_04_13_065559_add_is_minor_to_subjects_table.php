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
        // Only add duration_hours if it doesn't exist yet
        if (!Schema::hasColumn('subjects', 'duration_hours')) {
            $table->integer('duration_hours')->default(3)->after('type');
        }

        // Only add is_minor if it doesn't exist yet
        if (!Schema::hasColumn('subjects', 'is_minor')) {
            $table->boolean('is_minor')->default(false);
        }
    });
}

/** * Reverse the migrations.
 */
public function down(): void
{
    Schema::table('subjects', function (Blueprint $table) {
        // Drop the columns we added if we roll back
        $table->dropColumn(['duration_hours', 'is_minor']);
    });
}
};
