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
            $table->string('application_type'); // 'simple_patent' or 'patent'
            $table->string('patent_title');
            $table->text('patent_description');
            $table->boolean('from_grant_research')->comment('Whether the invention comes from research/community service that received grant funding');
            $table->boolean('self_funded')->comment('Whether self-funding will be used');
            $table->text('media_link')->nullable()->comment('Link to video/poster and leaflet (must be accessible). Format: A3 poster containing invention advantages and price');
            $table->text('inventors')->nullable();
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
