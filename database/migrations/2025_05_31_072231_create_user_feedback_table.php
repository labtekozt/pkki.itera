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
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // User information
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Page/context information
            $table->string('page_url', 500);
            $table->string('page_title', 255)->nullable();
            
            // Feedback data
            $table->tinyInteger('rating')->unsigned()->comment('1-5 star rating');
            $table->json('difficulty_areas')->nullable()->comment('Array of difficulty areas');
            $table->enum('age_range', [
                'under_30', '30_45', '46_60', '61_70', 'over_70', 'prefer_not_say'
            ])->nullable();
            $table->enum('tech_comfort', [
                'beginner', 'basic', 'intermediate', 'advanced'
            ])->nullable();
            $table->enum('device_type', ['mobile', 'desktop', 'both'])->nullable();
            
            // Technical information
            $table->json('browser_info')->nullable()->comment('Browser and device details');
            
            // User comments and contact
            $table->text('comments')->nullable();
            $table->boolean('contact_permission')->default(false);
            
            // Session and behavioral data
            $table->json('session_data')->nullable()->comment('Time spent, clicks, etc.');
            
            // Admin processing
            $table->boolean('is_critical')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['rating', 'created_at']);
            $table->index(['is_critical', 'processed_at']);
            $table->index(['page_url', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
