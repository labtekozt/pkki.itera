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
        Schema::create('workflow_stage_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_stage_id')
                  ->constrained('workflow_stages')
                  ->onDelete('cascade');
            $table->foreignUuid('document_requirement_id')
                  ->constrained('document_requirements')
                  ->onDelete('cascade');
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            // Add unique constraint to prevent duplicates
            $table->unique(['workflow_stage_id', 'document_requirement_id'], 'unique_stage_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_stage_requirements');
    }
};
