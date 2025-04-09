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
        // Create new submissions table with improved structure
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_type_id')
                ->constrained('submission_types')
                ->onDelete('restrict');
            $table->foreignUuid('current_stage_id')
                ->nullable()
                ->constrained('workflow_stages')
                ->onDelete('restrict');
            $table->string('title');
            $table->enum('status', [
                'draft',
                'submitted',
                'in_review',
                'revision_needed',
                'approved',
                'rejected',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->string('certificate')->nullable();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
