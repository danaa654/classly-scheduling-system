<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('faculties', function (Blueprint $table) {
        // 'pending' is default for Deans, 'approved' will be for Admin adds
        $table->string('status')->default('approved')->after('id'); 
        
        // Foreign key to track which user made the request
        $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
        
        // Optional: Rejection reason if the Registrar declines
        $table->text('rejection_reason')->nullable()->after('status');
    });
}

public function down(): void
{
    Schema::table('faculties', function (Blueprint $table) {
        $table->dropForeign(['requested_by']);
        $table->dropColumn(['status', 'requested_by', 'rejection_reason']);
    });
}
};
