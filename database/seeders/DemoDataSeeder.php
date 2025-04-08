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

        // Create a new patent submission
        $submission = Submission::create([
            'id' => Str::uuid(),
            'submission_type_id' => $paten->id,
            'current_stage_id' => $firstStage->id,
            'title' => 'Sistem Pelacakan Kekayaan Intelektual',
            'status' => 'in_review',
            'inventor_details' => 'Dr. Budi Santoso, Institut Teknologi Sumatera',
            'metadata' => json_encode([
                'invention_type' => 'Software',
                'technology_field' => 'Information Technology'
            ]),
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(25),
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
        // Same pattern as above, but for brand submission
        // Implementation details would be similar to createPatentSubmission
        // with appropriate changes for brand-specific requirements and stages
    }

    private function createCopyrightSubmission($haki, $user, $admin): void
    {
        // Similar implementation for copyright submission
    }

    private function createDesignSubmission($design, $user, $admin): void
    {
        // Similar implementation for industrial design submission
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
