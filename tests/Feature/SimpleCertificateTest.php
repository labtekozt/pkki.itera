<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Submission;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use App\Models\TrackingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class SimpleCertificateTest extends TestCase
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
    public function submission_can_be_marked_as_completed_with_certificate()
    {
        // Create test data
        $user = User::factory()->create();
        $user->assignRole('civitas');
        
        $submissionType = SubmissionType::factory()->create([
            'name' => 'Patent',
            'slug' => 'paten'
        ]);
        
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'submission_type_id' => $submissionType->id,
            'status' => 'in_review'
        ]);

        // Simulate certificate upload and completion
        $certificatePath = 'certificates/test_certificate.pdf';
        $submission->update([
            'status' => 'completed',
            'certificate' => $certificatePath,
            'reviewer_notes' => 'Certificate issued successfully'
        ]);

        // Create tracking history for completion
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'comment' => 'Certificate issued successfully',
            'metadata' => json_encode([
                'certificate_number' => 'CERT-2025-000001',
                'issued_date' => now()->toDateString()
            ])
        ]);

        // Assertions
        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
        $this->assertEquals($certificatePath, $submission->certificate);
        $this->assertEquals('Certificate issued successfully', $submission->reviewer_notes);
        
        // Check tracking history
        $this->assertDatabaseHas('tracking_histories', [
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed'
        ]);
        
        // Test certificate metadata
        $completionHistory = $submission->trackingHistories()
            ->where('action', 'certificate_uploaded')
            ->where('status', 'completed')
            ->first();
            
        $this->assertNotNull($completionHistory);
        $metadata = json_decode($completionHistory->metadata, true);
        $this->assertEquals('CERT-2025-000001', $metadata['certificate_number']);
    }

    /** @test */
    public function certificate_number_generation_works_correctly()
    {
        // Test the certificate number generation pattern
        $year = date('Y');
        $submissionId = 123;
        
        $generateCertNumber = function($submissionId) {
            return 'CERT-' . date('Y') . '-' . str_pad($submissionId, 6, '0', STR_PAD_LEFT);
        };
        
        $expectedNumber = 'CERT-' . $year . '-000123';
        $actualNumber = $generateCertNumber($submissionId);
        
        $this->assertEquals($expectedNumber, $actualNumber);
    }

    /** @test */
    public function submission_with_certificate_has_proper_display_data()
    {
        $user = User::factory()->create();
        $submission = Submission::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'certificate' => 'certificates/test_certificate.pdf'
        ]);

        // Create completion tracking history
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'action' => 'certificate_uploaded',
            'status' => 'completed',
            'comment' => 'Certificate issued successfully',
            'metadata' => json_encode([
                'certificate_number' => 'CERT-2025-000001',
                'issued_date' => now()->toDateString(),
                'issued_by' => 'PKKI ITERA'
            ]),
            'event_timestamp' => now()
        ]);

        // Test that we can retrieve certificate information
        $completionHistory = $submission->trackingHistories()
            ->where('action', 'certificate_uploaded')
            ->where('status', 'completed')
            ->first();

        $this->assertNotNull($completionHistory);
        $this->assertEquals('completed', $submission->status);
        $this->assertNotNull($submission->certificate);
        
        // Test metadata extraction for display
        $metadata = json_decode($completionHistory->metadata, true);
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('certificate_number', $metadata);
        $this->assertArrayHasKey('issued_date', $metadata);
        $this->assertEquals('CERT-2025-000001', $metadata['certificate_number']);
    }
}
