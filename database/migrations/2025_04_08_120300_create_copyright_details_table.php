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
        Schema::create('copyright_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->string('work_type'); // literary, musical, artistic, etc.
            $table->text('work_description');
            $table->year('creation_year');
            $table->boolean('is_published')->default(false);
            $table->date('publication_date')->nullable();
            $table->string('publication_place')->nullable();
            $table->text('authors')->nullable();
            $table->text('previous_registrations')->nullable();
            $table->text('derivative_works')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('registration_date')->nullable();
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
        Schema::dropIfExists('copyright_details');
    }
};
