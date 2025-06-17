<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating workflow stages...');
        
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
        
        $this->command->info('Workflow stages seeding completed.');
    }

    private function createStages($submissionTypeId, $stages): void
    {
        foreach ($stages as $stageData) {
            $requiredDocs = $stageData['required_documents'] ?? [];
            unset($stageData['required_documents']);
            
            $stage = WorkflowStage::firstOrCreate(
                [
                    'submission_type_id' => $submissionTypeId,
                    'code' => $stageData['code']
                ],
                [
                    'id' => Str::uuid(),
                    'name' => $stageData['name'],
                    'order' => $stageData['order'],
                    'description' => $stageData['description'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            if ($stage->wasRecentlyCreated) {
                $this->command->info("✅ Created workflow stage: {$stageData['name']}");
                
                // Create relationships with document requirements
                if (!empty($requiredDocs)) {
                    $order = 1;
                    foreach ($requiredDocs as $docCode) {
                        $docRequirement = DocumentRequirement::where('code', $docCode)->first();
                        if ($docRequirement) {
                            WorkflowStageRequirement::firstOrCreate(
                                [
                                    'workflow_stage_id' => $stage->id,
                                    'document_requirement_id' => $docRequirement->id,
                                ],
                                [
                                    'id' => Str::uuid(),
                                    'is_required' => true,
                                    'order' => $order++,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            } else {
                $this->command->info("ℹ️ Workflow stage already exists: {$stageData['name']}");
            }
        }
    }
}
