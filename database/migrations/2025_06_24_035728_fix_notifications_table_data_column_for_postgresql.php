<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert the data column from text to jsonb for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            // First, check if there's any existing data
            $hasData = DB::table('notifications')->exists();
            
            if ($hasData) {
                // Update existing text data to valid JSON format if needed
                DB::statement("
                    UPDATE notifications 
                    SET data = CASE 
                        WHEN data::text ~ '^\\s*[\\{\\[]' THEN data::text
                        ELSE '{\"message\": \"' || replace(data, '\"', '\\\"') || '\"}'::text
                    END
                    WHERE data IS NOT NULL
                ");
            }
            
            // Change column type to jsonb
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');
        } else {
            // For other databases, use Laravel's json column type
            Schema::table('notifications', function (Blueprint $table) {
                $table->json('data')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Convert back to text
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                $table->text('data')->change();
            });
        }
    }
};
