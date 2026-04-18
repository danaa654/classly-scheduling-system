<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            // Check if column exists first to prevent that "Duplicate Column" error
            if (!Schema::hasColumn('faculties', 'status')) {
                $table->string('status')->default('pending')->after('department');
            }
            
            if (!Schema::hasColumn('faculties', 'requested_by')) {
                $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('faculties', 'remarks')) {
                $table->text('remarks')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropColumn(['status', 'requested_by', 'remarks']);
        });
    }
};