<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submission_documents', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
        });

        // Update existing documents based on their status
        // Only 'pending' and 'approved' documents should be active
        DB::table('submission_documents')
            ->whereNotIn('status', ['pending', 'approved'])
            ->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_documents', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
