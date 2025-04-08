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
        if (Schema::hasTable('document')) {
            Schema::rename('document', 'documents');
        }

        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('uri');
                $table->string('title');
                $table->string('mimetype')->nullable();
                $table->bigInteger('size')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('documents', function (Blueprint $table) {
                if (!Schema::hasColumn('documents', 'mimetype')) {
                    $table->string('mimetype')->nullable()->after('title');
                }

                if (!Schema::hasColumn('documents', 'size')) {
                    $table->bigInteger('size')->nullable()->after('mimetype');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn(['mimetype', 'size']);
            });
            Schema::rename('documents', 'document');
        }
    }
};
