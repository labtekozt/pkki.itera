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
            $table->string("design_title"); // Title of the industrial design
            $table->string("inventors_name"); // Name of the inventor(s)
            $table->string('design_type'); // Type of industrial design (e.g., product, packaging)
            $table->text('design_description'); // Description of the design
            $table->text('novelty_statement'); // Statement of novelty
            $table->text('designer_information'); // Information about the designer
            $table->string('locarno_class')->nullable(); // Locarno Classification
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
