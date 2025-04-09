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
        Schema::create('industrial_design_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->string('design_type');
            $table->text('design_description');
            $table->text('novelty_statement');
            $table->text('designer_information');
            $table->string('locarno_class')->nullable(); // Locarno Classification
            $table->date('filing_date')->nullable();
            $table->string('application_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('expiration_date')->nullable();
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
        Schema::dropIfExists('industrial_design_details');
    }
};
