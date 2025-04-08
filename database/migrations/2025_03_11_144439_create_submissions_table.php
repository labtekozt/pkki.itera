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
        Schema::disableForeignKeyConstraints();

        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->index();
            $table->foreignUuid('paten_id')->nullable()->constrained('submission_paten');
            $table->uuid('brand_id')->nullable()->constrained('submission_brand');
            $table->foreignUuid('haki_id')->nullable()->constrained('submission_haki');
            $table->foreignUuid('industrial_design_id')->nullable()->constrained('submission_industrial_design');
            $table->string('certificate')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->boolean('has_consult')->default(false);
            $table->text('inventor')->nullable();
            $table->foreignUuid('user_id')->constrained();
            $table->dateTime('created_at');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
