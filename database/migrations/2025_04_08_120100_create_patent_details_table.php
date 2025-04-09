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
        Schema::create('patent_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->string('patent_type'); // e.g., utility, design, plant, etc.
            $table->text('invention_description');
            $table->text('technical_field')->nullable();
            $table->text('background')->nullable();
            $table->string('patent_status')->nullable();
            $table->text('inventor_details');
            $table->date('filing_date')->nullable();
            $table->string('application_number')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('publication_number')->nullable();
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patent_details');
    }
};
