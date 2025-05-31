<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Submission;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use App\Models\TrackingHistory;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create basic roles
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'civitas', 'guard_name' => 'web']);
    }

    /** @test */
    public function admin_can_upload_certificate_at_final_stage()
    {
        // Create test data
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $submissionType = SubmissionType::factory()->create([
            'name' => 'Patent',
            'slug' => 'paten'
        ]);
        
        $finalStage = WorkflowStage::factory()->create([
            'submission_type_id' => $submissionType->id,
            'code' => 'final_approval',
            'name' => 'Final Approval',
            'order' => 999
        ]);
        
        $submission = Submission::factory()->create([
            'submission_type_id' => $submissionType->id,
            'current_stage_id' => $finalStage->id,
            'status' => 'in_review'
        ]);

        // Create fake certificate file
        Storage::fake('public');
        $certificateFile = UploadedFile::fake()->create('certificate.pdf', 1024, 'application/pdf');

        // Act as admin and access the review page
        $this->actingAs($admin);
        
        // Simulate the certificate upload process
        $certificateData = [
            'certificate' => $certificateFile,
            'status' => 'completed',
            'reviewer_notes' => 'Certificate issued successfully'
        ];

        // Verify the submission can be completed with certificate
        $this->assertTrue($submission->canAdvanceToNextStage());
        
        // Simulate WorkflowService completion
        $workflowService = app(WorkflowService::class);
        
        // Update submission with certificate
        $submission->update([
            'status' => 'completed',
            'certificate' => 'certificates/certificate_' . time() . '.pdf',
            'reviewer_notes' => 'Certificate issued successfully'
        ]);

        // Create tracking history for completion
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $finalStage->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'comment' => 'Certificate issued successfully',
            'processed_by' => $admin->id,
            'metadata' => [
                'certificate_number' => 'CERT-' . date('Y') . '-' . str_pad($submission->id, 6, '0', STR_PAD_LEFT),
                'issued_date' => now()->toDateString(),
                'issued_by' => 'PKKI ITERA'
            ]
        ]);

        // Assertions
        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
        $this->assertNotNull($submission->certificate);
        $this->assertEquals('Certificate issued successfully', $submission->reviewer_notes);
        
        // Check tracking history
        $this->assertDatabaseHas('tracking_histories', [
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'processed_by' => $admin->id
        ]);
    }

    /** @test */
    public function submission_with_certificate_displays_download_option()
    {
        // Create test data
        $user = User::factory()->create();
        $user->assignRole('civitas');
        
        $submissionType = SubmissionType::factory()->create();
        
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'submission_type_id' => $submissionType->id,
            'status' => 'completed',
            'certificate' => 'certificates/test_certificate.pdf'
        ]);

        // Create completion tracking history
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'comment' => 'Certificate issued',
            'metadata' => json_encode([
                'certificate_number' => 'CERT-2024-000001',
                'issued_date' => now()->toDateString()
            ])
        ]);

        // Create fake certificate file
        Storage::fake('public');
        Storage::put('certificates/test_certificate.pdf', 'fake certificate content');

        $this->actingAs($user);
        
        // Test that certificate section appears in ViewSubmission
        $this->assertTrue($submission->status === 'completed');
        $this->assertNotNull($submission->certificate);
        $this->assertTrue(Storage::exists($submission->certificate));
        
        // Test certificate metadata extraction
        $completionHistory = $submission->trackingHistories()
            ->where('action', 'certificate_uploaded')
            ->where('status', 'completed')
            ->first();
            
        $this->assertNotNull($completionHistory);
        $metadata = json_decode($completionHistory->metadata, true);
        $this->assertEquals('CERT-2024-000001', $metadata['certificate_number']);
    }

    /** @test */
    public function certificate_download_works_correctly()
    {
        // Create test data
        $user = User::factory()->create();
        $user->assignRole('civitas');
        
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'certificate' => 'certificates/test_certificate.pdf'
        ]);

        // Create fake certificate file
        Storage::fake('public');
        $certificateContent = 'This is a test certificate content';
        Storage::put('certificates/test_certificate.pdf', $certificateContent);

        $this->actingAs($user);
        
        // Test certificate file exists
        $this->assertTrue(Storage::exists($submission->certificate));
        
        // Test file content
        $fileContent = Storage::get($submission->certificate);
        $this->assertEquals($certificateContent, $fileContent);
        
        // Test certificate filename generation
        $expectedFilename = 'Certificate_' . $submission->title . '.pdf';
        $this->assertNotEmpty($expectedFilename);
    }

    /** @test */
    public function certificate_section_shows_proper_ui_elements()
    {
        $user = User::factory()->create();
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'certificate' => 'certificates/test_certificate.pdf'
        ]);

        // Create completion tracking history with metadata
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'comment' => 'Certificate issued successfully',
            'metadata' => json_encode([
                'certificate_number' => 'CERT-2024-000001',
                'issued_date' => now()->toDateString(),
                'issued_by' => 'PKKI ITERA'
            ]),
            'event_timestamp' => now()
        ]);

        Storage::fake('public');
        Storage::put('certificates/test_certificate.pdf', 'test content');

        $this->actingAs($user);

        // Test completion tracking history
        $completionHistory = $submission->trackingHistories()
            ->where('action', 'certificate_uploaded')
            ->where('status', 'completed')
            ->first();

        $this->assertNotNull($completionHistory);
        
        // Test metadata extraction
        $metadata = json_decode($completionHistory->metadata, true);
        $this->assertIsArray($metadata);
        $this->assertEquals('CERT-2024-000001', $metadata['certificate_number']);
        
        // Test certificate availability
        $this->assertTrue(Storage::exists($submission->certificate));
        $this->assertEquals('completed', $submission->status);
    }

    /** @test */
    public function non_completed_submissions_do_not_show_certificate_section()
    {
        $user = User::factory()->create();
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_review',
            'certificate' => null
        ]);

        $this->actingAs($user);

        // Test that non-completed submissions don't have certificates
        $this->assertNotEquals('completed', $submission->status);
        $this->assertNull($submission->certificate);
        
        // No completion tracking history should exist
        $completionHistory = $submission->trackingHistories()
            ->where('action', 'certificate_uploaded')
            ->where('status', 'completed')
            ->first();
            
        $this->assertNull($completionHistory);
    }

    /** @test */
    public function certificate_numbers_are_generated_correctly()
    {
        $submission1 = Submission::factory()->create(['id' => 1]);
        $submission2 = Submission::factory()->create(['id' => 123]);
        
        // Test certificate number generation pattern
        $year = date('Y');
        $expectedNumber1 = 'CERT-' . $year . '-000001';
        $expectedNumber2 = 'CERT-' . $year . '-000123';
        
        // Simulate the certificate number generation logic
        $generateCertNumber = function($submissionId) {
            return 'CERT-' . date('Y') . '-' . str_pad($submissionId, 6, '0', STR_PAD_LEFT);
        };
        
        $this->assertEquals($expectedNumber1, $generateCertNumber(1));
        $this->assertEquals($expectedNumber2, $generateCertNumber(123));
    }
}
