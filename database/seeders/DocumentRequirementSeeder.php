<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use App\Models\SubmissionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        // Get submission type IDs
        $paten = SubmissionType::where('slug', 'paten')->first();
        $brand = SubmissionType::where('slug', 'brand')->first();
        $haki = SubmissionType::where('slug', 'haki')->first();
        $industrialDesign = SubmissionType::where('slug', 'industrial_design')->first();

        if (!$paten || !$brand || !$haki || !$industrialDesign) {
            $this->command->error('Submission types not found. Run SubmissionTypeSeeder first.');
            return;
        }

        // Patent requirements
        $patenRequirements = [
            [
                'code' => 'patent_checklist',
                'standard_code' => 'STPAT-CHKL-001',
                'name' => 'Checklist Paten',
                'description' => 'Dokumen berisi daftar kelengkapan berkas permohonan paten yang telah dipersiapkan. Pastikan semua item telah dicentang dan dokumen disimpan dalam format PDF yang jelas dan dapat dibaca.',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'patent_description_form',
                'standard_code' => 'STPAT-DESC-002',
                'name' => 'Formulir Deskripsi Paten',
                'description' => 'Form 01 - Dokumen lengkap yang berisi DESKRIPSI teknis invensi, KLAIM perlindungan yang diminta, dan ABSTRAK ringkasan invensi. Ketiga bagian ini harus disusun secara berurutan dalam satu file. Format dokumen harus dalam doc/word agar dapat diperiksa dengan baik. Deskripsi harus menjelaskan invensi secara lengkap dan klaim harus menentukan perlindungan hukum yang diinginkan.',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'invention_drawings',
                'standard_code' => 'STPAT-DRAW-003',
                'name' => 'Gambar Invensi',
                'description' => 'Form 02 - Gambar teknis yang menjelaskan invensi secara visual. Harus dalam format PDF dan berkualitas baik (resolusi minimal 300 dpi). PENTING: Gambar tidak boleh mengandung teks penjelasan, hanya diperbolehkan memuat legenda (Gambar 1, 2, dst...) dan keterangan dengan poin-poin (a, b, c...). Pengecualian hanya untuk diagram alir yang boleh memuat teks. Gambar harus jelas menunjukkan semua bagian penting invensi dari sudut pandang yang relevan.',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'patent_application_form',
                'standard_code' => 'STPAT-APPL-004',
                'name' => 'Formulir Permohonan Paten',
                'description' => 'Form 03 - Dokumen resmi untuk pengajuan permohonan paten yang berisi informasi lengkap tentang inventor, pemohon, dan invensi. Formulir harus sudah ditandatangani oleh Kepala LP3M dan disimpan dalam format PDF berkualitas baik yang menunjukkan tanda tangan dengan jelas. Pastikan semua informasi diisi dengan lengkap dan akurat sesuai dengan data terbaru.',
                'required' => true,
                'order' => 4
            ],
            [
                'code' => 'substantive_patent_application',
                'standard_code' => 'STPAT-SUBS-005',
                'name' => 'Formulir Permohonan Substantif Paten',
                'description' => 'Form 06 - FORMULIR PERMOHONAN SUBSTANTIF PATEN (sudah di ttd kepala LP3M dalam format PDF)',
                'required' => true,
                'order' => 5
            ],
            [
                'code' => 'rights_transfer_letter',
                'standard_code' => 'STPAT-TRAN-006',
                'name' => 'Surat Pengalihan Hak kepada ITERA',
                'description' => 'Form 04 - SURAT PENGALIHAN HAK KEPADA ITERA (sudah di ttd kepala LP3M dan Inventor serta materai terutama pada sisi inventor dalam format PDF)',
                'required' => true,
                'order' => 6
            ],
            [
                'code' => 'invention_ownership_statement_signed',
                'standard_code' => 'STPAT-OWNS-007',
                'name' => 'Surat Pernyataan Kepemilikan Invensi yang Ditandatangani',
                'description' => 'Form 05 - SURAT PERNYATAAN KEPEMILIKAN INVENSI (yang sudah diberi materai dan di tanda tangani dalam format PDF)',
                'required' => true,
                'order' => 7
            ],
            [
                'code' => 'invention_ownership_statement_editable',
                'standard_code' => 'STPAT-OWNE-008',
                'name' => 'Surat Pernyataan Kepemilikan Invensi yang Dapat Diedit',
                'description' => 'Form 05 - SURAT PERNYATAAN KEPEMILIKAN INVENSI (dalam format doc/word, tanpa ttd dan dapat diedit)',
                'required' => true,
                'order' => 8
            ],
            [
                'code' => 'id_card_photos',
                'standard_code' => 'STPAT-IDPH-009',
                'name' => 'Foto KTP',
                'description' => 'Form 07 - Foto KTP Hak Cipta master (format doc/word)',
                'required' => true,
                'order' => 9
            ]
        ];

        // Brand requirements
        $brandRequirements = [
            [
                'code' => 'brand_logo',
                'standard_code' => 'STBRD-LOGO-001',
                'name' => 'Etiket/Label Merek',
                'description' => 'Gambar atau logo merek yang dimohonkan perlindungan. Etiket harus jelas, dengan ukuran minimal 5x5 cm dan maksimal 20x20 cm. Format file harus JPG/PNG dengan resolusi minimal 300 dpi dan ukuran maksimal 2 MB. Jika merek berupa logo 3D, sertakan gambar dari berbagai sisi yang menunjukkan bentuk keseluruhan merek.',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'applicant_signature',
                'standard_code' => 'STBRD-SIGN-002',
                'name' => 'Tanda Tangan Pemohon',
                'description' => 'Lembar yang berisi tanda tangan asli pemohon atau kuasanya yang bermaterai. Tanda tangan harus jelas, tidak boleh hasil scan atau fotokopi. Dokumen ini menjadi bukti persetujuan dan tanggung jawab pemohon atas informasi yang diberikan. Format file dalam bentuk PDF dengan resolusi yang jelas.',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'brand_declaration',
                'standard_code' => 'STBRD-DECL-003',
                'name' => 'Surat Pernyataan Kepemilikan Merek',
                'description' => 'Dokumen resmi yang menyatakan bahwa merek yang didaftarkan adalah asli milik pemohon dan tidak menjiplak atau meniru merek lain yang telah terdaftar. Surat pernyataan harus ditandatangani di atas materai 10.000 dan mencantumkan informasi lengkap pemohon serta merek yang didaftarkan. Format dokumen dalam bentuk PDF.',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'umk_recommendation_letter',
                'standard_code' => 'STBRD-UMKR-004',
                'name' => 'Surat Rekomendasi UKM Binaan',
                'description' => 'Dokumen asli berupa surat rekomendasi dari instansi pemerintah yang menyatakan bahwa pemohon adalah UKM binaan. Surat ini digunakan untuk mendapatkan keringanan biaya pendaftaran merek. Wajib dilampirkan jika pemohon adalah Usaha Mikro atau Usaha Kecil yang ingin mendapatkan fasilitas khusus. Surat harus mencantumkan kepala surat instansi, nomor surat, tanggal penerbitan, dan ditandatangani oleh pejabat yang berwenang. Format dokumen dalam bentuk PDF.',
                'required' => false,
                'order' => 4
            ],
            [
                'code' => 'umk_statement_letter',
                'standard_code' => 'STBRD-UMKS-005',
                'name' => 'Surat Pernyataan UMK Bermaterai',
                'description' => 'Dokumen pernyataan yang ditandatangani di atas materai yang menyatakan bahwa pemohon adalah Usaha Mikro atau Usaha Kecil. Surat ini harus mencantumkan informasi usaha seperti nama, alamat, jenis usaha, dan omzet tahunan. Dokumen ini melengkapi Surat Rekomendasi UKM dan wajib dilampirkan untuk mendapatkan keringanan biaya. Format pernyataan harus mengikuti template yang disediakan dan dalam bentuk PDF.',
                'required' => false,
                'order' => 5
            ]
        ];

        // Haki requirements
        $hakiRequirements = [
            [
                'code' => 'haki_statement',
                'standard_code' => 'STHAK-STMT-001',
                'name' => 'Surat Pernyataan Hak Cipta',
                'description' => 'Form 03 - Dokumen resmi yang menyatakan bahwa karya yang didaftarkan adalah asli dan benar-benar milik pencipta. Harus disertai tanda tangan dan materai dalam format PDF.',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'creator_address_form',
                'standard_code' => 'STHAK-ADDR-002',
                'name' => 'Form Alamat Pencipta',
                'description' => 'Form 04 - Formulir yang berisi data lengkap alamat dan kontak dari seluruh pencipta. Dokumen harus dalam format WORD agar dapat diedit jika diperlukan.',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'haki_application_form',
                'standard_code' => 'STHAK-APPL-003',
                'name' => 'Form Permohonan Pencatatan Hak Cipta & Lampiran',
                'description' => 'Form 06 - Formulir resmi untuk permohonan pencatatan hak cipta beserta seluruh lampirannya. Dokumen harus lengkap dan dalam format PDF.',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'haki_transfer_letter',
                'standard_code' => 'STHAK-TRAN-004',
                'name' => 'Surat Pengalihan Hak Cipta',
                'description' => 'Form 07 - Dokumen yang menyatakan pengalihan hak cipta dari pencipta kepada pihak lain (jika ada). Harus dilengkapi dengan tanda tangan dan materai dalam format PDF.',
                'required' => true,
                'order' => 4
            ],
            [
                'code' => 'haki_work_attachment',
                'standard_code' => 'STHAK-WORK-005',
                'name' => 'Lampiran Karya Hak Cipta',
                'description' => 'Form 05 - Dokumen yang berisi hasil karya yang akan didaftarkan hak ciptanya, seperti program komputer, buku, musik, atau karya lainnya. Harus dalam format PDF dengan kualitas yang baik.',
                'required' => true,
                'order' => 5
            ]
        ];

        // Industrial Design requirements
        $designRequirements = [
            [
                'code' => 'industrial_design_images',
                'standard_code' => 'STIND-IMGS-001',
                'name' => 'Gambar Desain Industri',
                'description' => 'Gambar atau foto yang menunjukkan desain industri dari berbagai sudut pandang (tampak depan, belakang, samping, atas, dan bawah). Gambar harus berwarna atau hitam-putih dengan kualitas tinggi yang menampilkan fitur-fitur desain dengan jelas. Format gambar harus dalam bentuk PDF dengan resolusi minimal 300 dpi.',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'industrial_design_description',
                'standard_code' => 'STIND-DESC-002',
                'name' => 'Uraian Desain Industri',
                'description' => 'Dokumen yang berisi penjelasan rinci tentang fitur-fitur utama dan keunggulan desain industri yang dimohonkan. Uraian harus mencakup informasi tentang bentuk, konfigurasi, komposisi garis atau warna yang memberikan kesan estetis pada produk. Format dokumen dalam bentuk DOC/PDF.',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'industrial_design_ownership_statement',
                'standard_code' => 'STIND-OWNS-003',
                'name' => 'Surat Pernyataan Kepemilikan Desain Industri',
                'description' => 'Dokumen resmi yang menyatakan bahwa pemohon adalah pemilik sah dari desain industri yang didaftarkan. Surat pernyataan harus ditandatangani di atas materai dan mencantumkan informasi lengkap pemohon serta pendesain. Format dokumen dalam bentuk PDF.',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'power_of_attorney',
                'standard_code' => 'STIND-POAT-004',
                'name' => 'Surat Kuasa',
                'description' => 'Dokumen yang memberikan wewenang kepada konsultan untuk mengajukan permohonan desain industri atas nama pemohon. Surat kuasa harus ditandatangani di atas materai, mencantumkan identitas pemberi dan penerima kuasa secara lengkap, serta lingkup kewenangan yang diberikan. Wajib disertakan jika permohonan diajukan melalui konsultan. Format dokumen dalam bentuk PDF.',
                'required' => false,
                'order' => 4
            ],
            [
                'code' => 'rights_transfer_statement',
                'standard_code' => 'STIND-TRAN-005',
                'name' => 'Surat Pernyataan Pengalihan Hak',
                'description' => 'Dokumen yang menyatakan pengalihan hak atas desain industri dari pendesain kepada pemohon. Wajib disertakan jika pemohon dan pendesain adalah pihak yang berbeda. Surat harus ditandatangani kedua belah pihak di atas materai dan mencantumkan informasi lengkap tentang desain yang dialihkan. Format dokumen dalam bentuk PDF.',
                'required' => false,
                'order' => 5
            ],
            [
                'code' => 'micro_small_business_certificate',
                'standard_code' => 'STIND-MSBC-006',
                'name' => 'Surat Keterangan UMK',
                'description' => 'Dokumen resmi yang menerangkan bahwa pemohon merupakan Usaha Mikro atau Usaha Kecil. Surat keterangan ini dikeluarkan oleh instansi yang berwenang dan dapat digunakan untuk mendapatkan keringanan biaya pendaftaran. Wajib disertakan jika pemohon merupakan UMK dan ingin mendapatkan fasilitas khusus. Format dokumen dalam bentuk PDF.',
                'required' => false,
                'order' => 6
            ],
            [
                'code' => 'establishment_deed',
                'standard_code' => 'STIND-ESTD-007',
                'name' => 'SK Akta Pendirian',
                'description' => 'Dokumen resmi berupa Surat Keputusan atau Akta Pendirian yang menunjukkan legalitas pemohon sebagai lembaga pendidikan atau lembaga penelitian dan pengembangan pemerintah. Dokumen ini diperlukan untuk verifikasi status pemohon dan dapat memberikan keuntungan dalam proses pendaftaran. Format dokumen dalam bentuk PDF.',
                'required' => false,
                'order' => 7
            ]
        ];

        // Create document requirements
        $this->createRequirements($paten->id, $patenRequirements);
        $this->createRequirements($brand->id, $brandRequirements);
        $this->createRequirements($haki->id, $hakiRequirements);
        $this->createRequirements($industrialDesign->id, $designRequirements);
    }

    private function createRequirements(string $typeId, array $requirements): void
    {
        foreach ($requirements as $requirement) {
            DocumentRequirement::firstOrCreate(
                [
                    'submission_type_id' => $typeId,
                    'code' => $requirement['code']
                ],
                array_merge($requirement, [
                    'id' => Str::uuid(),
                    'submission_type_id' => $typeId,
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}