<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the tracking_histories table to store submission workflow history.
     */
    public function up(): void
    {
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                ->comment('Reference to the submission being tracked')
                ->constrained('submissions')
                ->onDelete('cascade');
            $table->foreignUuid('stage_id')
                ->comment('Current workflow stage')
                ->constrained('workflow_stages')
                ->onDelete('restrict');
            $table->string('action')
                ->default('state_change')
                ->comment('Action performed (e.g., approve, reject, request_revision)');
            $table->json('metadata')
                ->nullable()
                ->comment('Additional contextual data about this tracking event');
            $table->enum('status', [
                'started',
                'in_progress',
                'approved',
                'rejected',
                'revision_needed',
                'objection',
                'completed'
            ])->default('started')
                ->comment('Current status of this tracking event');
            $table->text('comment')
                ->nullable()
                ->comment('User provided comment/notes');
            $table->foreignUuid('document_id')
                ->nullable()
                ->comment('Related document if this tracking is document-specific')
                ->constrained('documents')
                ->onDelete('set null');
            $table->foreignUuid('processed_by')
                ->nullable()
                ->comment('User who performed this action')
                ->constrained('users')
                ->onDelete('set null');
            // Track both previous and next stage for transitions
            $table->foreignUuid('previous_stage_id')
                ->nullable()
                ->comment('Previous workflow stage (for transitions)')
                ->constrained('workflow_stages')
                ->onDelete('set null');
            $table->string('source_status')
                ->nullable()
                ->comment('Status before this tracking event');
            $table->string('target_status')
                ->nullable()
                ->comment('Status after this tracking event');
            $table->string('event_type')
                ->nullable()
                ->default('state_change')
                ->comment('Type of event (e.g., state_change, document_update, review_decision)');
            $table->timestamp('resolved_at')
                ->nullable()
                ->comment('When an issue was resolved (for revision requests)');
            $table->timestamp('event_timestamp')
                ->useCurrent()
                ->comment('When this event occurred');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better query performance
            $table->index(['submission_id', 'created_at']);
            $table->index(['status', 'submission_id']);
            $table->index(['event_type', 'submission_id']);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the tracking_histories table.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_histories');
    }
};
