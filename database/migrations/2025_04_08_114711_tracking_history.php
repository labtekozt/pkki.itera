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
        Schema::create('tracking_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->foreignUuid('stage_id')
                  ->constrained('workflow_stages')
                  ->onDelete('restrict');
            $table->enum('status', [
                'started', 
                'in_progress', 
                'approved', 
                'rejected', 
                'revision_needed', 
                'objection', 
                'completed'
            ]);
            $table->text('comment')->nullable();
            $table->foreignUuid('document_id')
                  ->nullable()
                  ->constrained('documents')
                  ->onDelete('set null');
            $table->foreignUuid('processed_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_history');
    }
};