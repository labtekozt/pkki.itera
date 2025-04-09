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
        Schema::create('brand_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')
                  ->constrained('submissions')
                  ->onDelete('cascade');
            $table->string('application_type')->comment('Tipe permohonan: merek dagang, jasa, kolektif, atau dagang&jasa');
            $table->date('application_date')->comment('Tanggal pengajuan permohonan merek');
            $table->string('application_origin')->comment('Asal permohonan merek yang diajukan');
            $table->string('application_category')->comment('Kategori permohonan: UMKM atau UMUM');
            $table->string('brand_type')->comment('Tipe merek: kata, logo, kombinasi, suara, dll');
            $table->string('brand_label')->comment('Label merek yang diajukan');
            $table->string('brand_label_reference')->nullable()->comment('Nama referensi dari label merek');
            $table->text('brand_label_description')->comment('Deskripsi rinci tentang label merek yang diajukan');
            $table->text('brand_color_elements')->nullable()->comment('Elemen warna yang terdapat dalam label merek');
            $table->text('foreign_language_translation')->nullable()->comment('Terjemahan Bahasa Indonesia jika merek menggunakan bahasa asing');
            $table->text('disclaimer')->nullable()->comment('Pernyataan penolakan hak eksklusif atas elemen tertentu dalam merek');
            $table->string('priority_number')->nullable()->comment('Nomor prioritas jika mengklaim hak prioritas');
            $table->string('nice_classes')->comment('Kelas klasifikasi Nice untuk merek');
            $table->text('goods_services_search')->nullable()->comment('Kata kunci untuk pencarian uraian barang/jasa');
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
        Schema::dropIfExists('brand_details');
    }
};
