<?php

namespace Database\Seeders;

use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowStageSeeder extends Seeder
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

        // Patent stages
        $patenStages = [
            [
                'code' => 'paten_submission',
                'name' => 'Pengajuan Dokumen',
                'order' => 1,
                'description' => 'Tahap pengajuan dokumen permohonan paten',
                'required_documents' => ['paten_desc', 'paten_claims', 'paten_abstract']
            ],
            [
                'code' => 'paten_formal_examination',
                'name' => 'Pemeriksaan Formal',
                'order' => 2,
                'description' => 'Pemeriksaan kelengkapan dokumen permohonan'
            ],
            [
                'code' => 'paten_announcement',
                'name' => 'Pengumuman',
                'order' => 3,
                'description' => 'Pengumuman permohonan paten'
            ],
            [
                'code' => 'paten_substantive_examination',
                'name' => 'Pemeriksaan Substantif',
                'order' => 4,
                'description' => 'Pemeriksaan substantif oleh pemeriksa paten'
            ],
            [
                'code' => 'paten_decision',
                'name' => 'Keputusan',
                'order' => 5,
                'description' => 'Keputusan permohonan paten'
            ]
        ];

        // Brand/Trademark stages
        $brandStages = [
            [
                'code' => 'brand_submission',
                'name' => 'Pengajuan Permohonan',
                'order' => 1,
                'description' => 'Tahap pengajuan permohonan merek',
                'required_documents' => ['brand_logo', 'brand_class', 'brand_declaration']
            ],
            [
                'code' => 'brand_formal_examination',
                'name' => 'Pemeriksaan Formal',
                'order' => 2,
                'description' => 'Pemeriksaan kelengkapan dokumen permohonan'
            ],
            [
                'code' => 'brand_publication',
                'name' => 'Publikasi',
                'order' => 3,
                'description' => 'Pengumuman permohonan merek di Berita Resmi Merek'
            ],
            [
                'code' => 'brand_substantive_examination',
                'name' => 'Pemeriksaan Substantif',
                'order' => 4,
                'description' => 'Pemeriksaan substantif oleh pemeriksa merek'
            ],
            [
                'code' => 'brand_decision',
                'name' => 'Keputusan',
                'order' => 5,
                'description' => 'Keputusan permohonan merek'
            ]
        ];

        // Copyright stages
        $hakiStages = [
            [
                'code' => 'haki_submission',
                'name' => 'Pengajuan Permohonan',
                'order' => 1,
                'description' => 'Tahap pengajuan permohonan hak cipta',
                'required_documents' => ['haki_work', 'haki_description', 'haki_declaration']
            ],
            [
                'code' => 'haki_verification',
                'name' => 'Verifikasi Administratif',
                'order' => 2,
                'description' => 'Verifikasi kelengkapan dan keabsahan dokumen'
            ],
            [
                'code' => 'haki_substantive_check',
                'name' => 'Pemeriksaan Substantif',
                'order' => 3,
                'description' => 'Pemeriksaan keaslian ciptaan'
            ],
            [
                'code' => 'haki_decision',
                'name' => 'Keputusan',
                'order' => 4,
                'description' => 'Keputusan permohonan hak cipta'
            ]
        ];

        // Industrial Design stages
        $designStages = [
            [
                'code' => 'design_submission',
                'name' => 'Pengajuan Permohonan',
                'order' => 1,
                'description' => 'Tahap pengajuan permohonan desain industri',
                'required_documents' => ['design_images', 'design_description', 'design_statement']
            ],
            [
                'code' => 'design_formal_examination',
                'name' => 'Pemeriksaan Formal',
                'order' => 2,
                'description' => 'Pemeriksaan kelengkapan dokumen permohonan'
            ],
            [
                'code' => 'design_announcement',
                'name' => 'Pengumuman',
                'order' => 3,
                'description' => 'Pengumuman permohonan desain industri'
            ],
            [
                'code' => 'design_substantive_examination',
                'name' => 'Pemeriksaan Substantif',
                'order' => 4,
                'description' => 'Pemeriksaan substantif oleh pemeriksa desain'
            ],
            [
                'code' => 'design_decision',
                'name' => 'Keputusan',
                'order' => 5,
                'description' => 'Keputusan permohonan desain industri'
            ]
        ];

        // Create workflow stages
        $this->createStages($paten->id, $patenStages);
        $this->createStages($brand->id, $brandStages);
        $this->createStages($haki->id, $hakiStages);
        $this->createStages($industrialDesign->id, $designStages);
    }

    private function createStages(string $typeId, array $stages): void
    {
        foreach ($stages as $stage) {
            $requiredDocuments = $stage['required_documents'] ?? [];

            WorkflowStage::firstOrCreate(
                [
                    'submission_type_id' => $typeId,
                    'code' => $stage['code']
                ],
                array_merge($stage, [
                    'id' => Str::uuid(),
                    'submission_type_id' => $typeId,
                    'required_documents' => json_encode($requiredDocuments),
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
