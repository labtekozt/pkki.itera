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
                'code' => 'paten_desc',
                'name' => 'Deskripsi Paten',
                'description' => 'Dokumen yang berisi penjelasan lengkap tentang invensi',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'paten_claims',
                'name' => 'Klaim',
                'description' => 'Bagian dari permohonan yang menjelaskan inti invensi yang dimintakan perlindungan',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'paten_abstract',
                'name' => 'Abstrak',
                'description' => 'Ringkasan dari deskripsi dan klaim paten',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'paten_drawings',
                'name' => 'Gambar Teknik',
                'description' => 'Gambar teknik yang menjelaskan invensi',
                'required' => false,
                'order' => 4
            ]
        ];

        // Brand/Trademark requirements
        $brandRequirements = [
            [
                'code' => 'brand_logo',
                'name' => 'Logo Merek',
                'description' => 'Gambar logo yang dimohonkan sebagai merek',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'brand_class',
                'name' => 'Daftar Kelas Barang/Jasa',
                'description' => 'Daftar kelas barang/jasa yang akan dilindungi merek',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'brand_declaration',
                'name' => 'Surat Pernyataan Kepemilikan Merek',
                'description' => 'Surat pernyataan bahwa merek adalah milik pemohon',
                'required' => true,
                'order' => 3
            ]
        ];

        // Copyright requirements
        $hakiRequirements = [
            [
                'code' => 'haki_work',
                'name' => 'Ciptaan',
                'description' => 'Hasil karya yang akan didaftarkan hak cipta',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'haki_description',
                'name' => 'Deskripsi Ciptaan',
                'description' => 'Penjelasan tentang ciptaan yang didaftarkan',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'haki_declaration',
                'name' => 'Surat Pernyataan Kepemilikan',
                'description' => 'Pernyataan bahwa ciptaan adalah milik pemohon',
                'required' => true,
                'order' => 3
            ]
        ];

        // Industrial Design requirements
        $designRequirements = [
            [
                'code' => 'design_images',
                'name' => 'Gambar Desain',
                'description' => 'Gambar atau foto yang menunjukkan desain dari berbagai sudut pandang',
                'required' => true,
                'order' => 1
            ],
            [
                'code' => 'design_description',
                'name' => 'Uraian Desain',
                'description' => 'Penjelasan tentang fitur desain industri yang dimohonkan',
                'required' => true,
                'order' => 2
            ],
            [
                'code' => 'design_statement',
                'name' => 'Pernyataan Kepemilikan',
                'description' => 'Pernyataan bahwa desain adalah milik pemohon',
                'required' => true,
                'order' => 3
            ],
            [
                'code' => 'design_prototype',
                'name' => 'Contoh Fisik/Prototipe',
                'description' => 'Foto atau gambar prototipe (jika ada)',
                'required' => false,
                'order' => 4
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