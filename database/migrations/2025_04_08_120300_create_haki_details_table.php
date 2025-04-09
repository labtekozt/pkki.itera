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
        Schema::create('haki_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                ->constrained('submissions')
                ->onDelete('cascade');
            $table->string('work_type'); // Type of creation (Jenis Ciptaan)
            $table->string('work_subtype')->nullable(); // Sub-type of creation (Sub Jenis Ciptaan)
            $table->enum('haki_category', ['computer', 'non_computer']); // Computer or Non-Computer Copyright
            $table->string('haki_title'); // Title of the work being applied for
            $table->text('work_description'); // Brief description of the work
            $table->date('first_publication_date')->nullable(); // When the work was first published in print/mass media
            $table->string('first_publication_place')->nullable(); // Where the work was first published
            $table->boolean('is_kkn_output')->default(false); // Whether it's a KKN (community service) output
            $table->boolean('from_grant_research')->default(false); // Whether it's from research/community service with grant funding
            $table->boolean('self_funded')->default(false); // Whether it will use self-funding
            $table->string('registration_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string("inventors_name")->nullable(); // Names of the inventors
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
        Schema::dropIfExists('haki_details');
    }
};
