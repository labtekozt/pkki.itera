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
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'inventor_details')) {
                $table->dropColumn('inventor_details');
            }
            
            if (Schema::hasColumn('submissions', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->text('inventor_details')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
