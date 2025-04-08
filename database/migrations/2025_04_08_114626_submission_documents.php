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
        Schema::create('submission_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                ->constrained('submissions')
                ->onDelete('cascade');
            $table->foreignUuid('document_id')
                ->constrained('documents')
                ->onDelete('restrict');
            $table->foreignUuid('requirement_id')
                ->nullable()
                ->constrained('document_requirements')
                ->onDelete('restrict');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'revision_needed',
                'replaced',
                'final'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_documents');
    }
};
