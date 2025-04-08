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

        Schema::create('submission_tracker', function (Blueprint $table) {
            $table->uuid('id')->primary()->foreign('submissions.id');
            $table->enum('status', ["diterima","ditolak","menunggu","revisi"]);
            $table->text('comment');
            $table->dateTime('create_at');
            $table->foreignUuid('draft_track')->constrained('document');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_tracker');
    }
};
