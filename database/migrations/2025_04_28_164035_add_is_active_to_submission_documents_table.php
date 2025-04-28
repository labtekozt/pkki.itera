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
            $table->boolean('is_active')->default(true)->after('status')->comment('Indicates if this document is active in the submission');
        });

        // Update existing records: set is_active to false for replaced or rejected documents
        DB::table('submission_documents')
            ->whereIn('status', ['replaced', 'rejected'])
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
