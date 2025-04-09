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
        Schema::create('trademark_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->string('trademark_type'); // word, design, combined, sound, etc.
            $table->text('description');
            $table->text('goods_services_description');
            $table->string('nice_classes'); // Nice Classification classes
            $table->boolean('has_color_claim')->default(false);
            $table->string('color_description')->nullable();
            $table->date('first_use_date')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('registration_date')->nullable();
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
        Schema::dropIfExists('trademark_details');
    }
};
