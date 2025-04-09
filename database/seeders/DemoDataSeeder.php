<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionType;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\PatentDetail;
use App\Models\TrademarkDetail;
use App\Models\CopyrightDetail;
use App\Models\IndustrialDesignDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->info('Demo seeder skipped in production environment.');
            return;
        }

        $this->command->info('Creating demo data for development environment...');

        // Get existing submission types
        $paten = SubmissionType::where('slug', 'paten')->first();
        $brand = SubmissionType::where('slug', 'brand')->first();
        $haki = SubmissionType::where('slug', 'haki')->first();
        $design = SubmissionType::where('slug', 'industrial_design')->first();

        // Get existing users
        $admin = User::where('email', 'superadmin@hki.itera.ac.id')->first();
        $user = User::where('email', 'superadmin@hki.itera.ac.id')->first();

        if (!$admin || !$user) {
            $this->command->error('Admin and regular users not found. Run UserSeeder first.');
            return;
        }

        // Create some demo submissions
        $this->createPatentSubmission($paten, $user, $admin);
        $this->createBrandSubmission($brand, $user, $admin);
        $this->createCopyrightSubmission($haki, $user, $admin);
        $this->createDesignSubmission($design, $user, $admin);

        // Create additional users with submissions in various stages
        $this->createAdditionalUsers();
    }

    private function createPatentSubmission($paten, $user, $admin): void
    {
        // Get first stage
        $firstStage = WorkflowStage::where('submission_type_id', $paten->id)
            ->orderBy('order')
            ->first();

        // Create submission record
        $submission = Submission::create([
            'id' => Str::uuid(),
            'title' => 'Sistem Pelacakan Kekayaan Intelektual',
            'submission_type_id' => $paten->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'in_review',
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
            'updated_at' => now(),
        ]);

        // Create patent-specific details in the patent_details table
        PatentDetail::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'patent_type' => 'utility',
            'invention_description' => 'Software untuk pelacakan proses kekayaan intelektual',
            'technical_field' => 'Information Technology',
            'inventor_details' => 'Dr. Budi Santoso, Institut Teknologi Sumatera',
            'filing_date' => now()->subDays(5),
        ]);

        // Create documents and link them to the submission
        $requirements = DocumentRequirement::where('submission_type_id', $paten->id)->get();

        foreach ($requirements as $requirement) {
            $document = Document::create([
                'id' => Str::uuid(),
                'uri' => 'demo/patents/' . Str::slug($requirement->name) . '.pdf',
                'title' => $requirement->name . ' - Sistem Pelacakan',
                'mimetype' => 'application/pdf',
                'size' => rand(100000, 5000000),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ]);

            SubmissionDocument::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'document_id' => $document->id,
                'requirement_id' => $requirement->id,
                'status' => 'approved',
                'notes' => null,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
            ]);
        }

        // Create tracking history
        $stages = WorkflowStage::where('submission_type_id', $paten->id)
            ->orderBy('order')
            ->limit(2)
            ->get();

        // First stage complete
        TrackingHistory::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'stage_id' => $stages[0]->id,
            'status' => 'started',
            'comment' => 'Permohonan paten telah diterima dan sedang dalam proses pemeriksaan awal.',
            'processed_by' => null,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        TrackingHistory::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'stage_id' => $stages[0]->id,
            'status' => 'approved',
            'comment' => 'Dokumen permohonan lengkap dan dapat dilanjutkan ke tahap pemeriksaan formal.',
            'processed_by' => $admin->id,
            'created_at' => now()->subDays(28),
            'updated_at' => now()->subDays(28),
        ]);

        // Second stage in progress
        TrackingHistory::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'stage_id' => $stages[1]->id,
            'status' => 'started',
            'comment' => 'Pemeriksaan formal telah dimulai.',
            'processed_by' => $admin->id,
            'created_at' => now()->subDays(25),
            'updated_at' => now()->subDays(25),
        ]);
    }

    private function createBrandSubmission($brand, $user, $admin): void
    {
        // Get first stage
        $firstStage = WorkflowStage::where('submission_type_id', $brand->id)
            ->orderBy('order')
            ->first();

        // Create submission record
        $submission = Submission::create([
            'id' => Str::uuid(),
            'title' => 'Logo ITERA',
            'submission_type_id' => $brand->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'submitted',
            'user_id' => $user->id,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(2),
        ]);

        // Create trademark-specific details
        TrademarkDetail::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'trademark_type' => 'combined',
            'description' => 'Logo ITERA dengan kombinasi lambang dan teks',
            'goods_services_description' => 'Layanan pendidikan tingkat universitas',
            'nice_classes' => '41',
            'has_color_claim' => true,
            'color_description' => 'Biru dan Putih',
        ]);

        // Create documents and link them to the submission
        $requirements = DocumentRequirement::where('submission_type_id', $brand->id)->get();

        foreach ($requirements as $requirement) {
            $document = Document::create([
                'id' => Str::uuid(),
                'uri' => 'demo/brands/' . Str::slug($requirement->name) . '.pdf',
                'title' => $requirement->name . ' - Logo ITERA',
                'mimetype' => 'application/pdf',
                'size' => rand(100000, 5000000),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ]);

            SubmissionDocument::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'document_id' => $document->id,
                'requirement_id' => $requirement->id,
                'status' => 'approved',
                'notes' => null,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
            ]);
        }
    }

    private function createCopyrightSubmission($haki, $user, $admin): void
    {
        // Get first stage
        $firstStage = WorkflowStage::where('submission_type_id', $haki->id)
            ->orderBy('order')
            ->first();

        // Create submission record
        $submission = Submission::create([
            'id' => Str::uuid(),
            'title' => 'Buku Panduan Penelitian ITERA',
            'submission_type_id' => $haki->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'approved',
            'user_id' => $user->id,
            'created_at' => now()->subMonths(2),
            'updated_at' => now()->subDays(10),
        ]);

        // Create copyright-specific details
        CopyrightDetail::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'work_type' => 'literary',
            'work_description' => 'Buku panduan penelitian untuk dosen dan mahasiswa ITERA',
            'creation_year' => now()->year - 1,
            'is_published' => true,
            'publication_date' => now()->subMonths(3),
            'publication_place' => 'Lampung',
            'authors' => 'Tim Penelitian ITERA',
        ]);

        // Create documents and link them to the submission
        $requirements = DocumentRequirement::where('submission_type_id', $haki->id)->get();

        foreach ($requirements as $requirement) {
            $document = Document::create([
                'id' => Str::uuid(),
                'uri' => 'demo/copyrights/' . Str::slug($requirement->name) . '.pdf',
                'title' => $requirement->name . ' - Buku Panduan',
                'mimetype' => 'application/pdf',
                'size' => rand(100000, 5000000),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ]);

            SubmissionDocument::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'document_id' => $document->id,
                'requirement_id' => $requirement->id,
                'status' => 'approved',
                'notes' => null,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
            ]);
        }
    }

    private function createDesignSubmission($design, $user, $admin): void
    {
        // Get first stage
        $firstStage = WorkflowStage::where('submission_type_id', $design->id)
            ->orderBy('order')
            ->first();

        // Create submission record
        $submission = Submission::create([
            'id' => Str::uuid(),
            'title' => 'Desain Kursi Ergonomis',
            'submission_type_id' => $design->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'revision_needed',
            'user_id' => $user->id,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(5),
        ]);

        // Create industrial design-specific details
        IndustrialDesignDetail::create([
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'design_type' => 'furniture',
            'design_description' => 'Kursi ergonomis untuk penggunaan jangka panjang',
            'novelty_statement' => 'Desain kursi dengan sandaran dan dukungan lumbar yang inovatif',
            'designer_information' => 'Tim Desain Teknik Industri ITERA',
            'locarno_class' => '06-01',
        ]);

        // Create documents and link them to the submission
        $requirements = DocumentRequirement::where('submission_type_id', $design->id)->get();

        foreach ($requirements as $requirement) {
            $document = Document::create([
                'id' => Str::uuid(),
                'uri' => 'demo/designs/' . Str::slug($requirement->name) . '.pdf',
                'title' => $requirement->name . ' - Desain Kursi',
                'mimetype' => 'application/pdf',
                'size' => rand(100000, 5000000),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ]);

            SubmissionDocument::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'document_id' => $document->id,
                'requirement_id' => $requirement->id,
                'status' => 'approved',
                'notes' => null,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(25),
            ]);
        }
    }

    private function createAdditionalUsers(): void
    {
        // Create 5 additional users with random submissions
        User::factory(5)
            ->create()
            ->each(function ($user) {
                $user->assignRole('civitas');
                // Create 1-3 submissions for each user
                $count = rand(1, 3);
                for ($i = 0; $i < $count; $i++) {
                    Submission::factory()
                        ->create(['user_id' => $user->id]);
                }
            });
    }
}
