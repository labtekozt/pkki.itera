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

        Schema::create('submission_brand', function (Blueprint $table) {
            $table->uuid('id')->primary()->foreign('submissions.brand_id');
            $table->string('kelas');
            $table->string('label');
            $table->foreignUuid('draft')->constrained('document');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_brand');
    }
};
