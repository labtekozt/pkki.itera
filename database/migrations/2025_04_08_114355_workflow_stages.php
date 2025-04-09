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
        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_type_id')
                ->constrained('submission_types')
                ->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('order');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_stages');
    }
};
