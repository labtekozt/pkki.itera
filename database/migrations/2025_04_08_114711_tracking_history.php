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
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                ->constrained('submissions')
                ->onDelete('cascade');
            $table->foreignUuid('stage_id')
                ->constrained('workflow_stages')
                ->onDelete('restrict');
            $table->string('action');
            $table->json('metadata')->nullable();
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
            // Track both previous and next stage for transitions
            $table->foreignUuid('previous_stage_id')
                ->nullable()
                ->constrained('workflow_stages')
                ->onDelete('set null');
            $table->string('source_status')->nullable();
            $table->string('target_status')->nullable();
            $table->string('event_type')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_histories');
    }
};
