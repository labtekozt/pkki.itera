<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionRepository
{
    /**
     * Create a new submission
     */
    public function createSubmission(array $data, array $documents = []): Submission
    {
        return DB::transaction(function () use ($data, $documents) {
            $submissionType = SubmissionType::findOrFail($data['submission_type_id']);
            
            // Set the first stage as current stage if submitted
            $currentStageId = null;
            if ($data['status'] === 'submitted') {
                $firstStage = $submissionType->firstStage();
                $currentStageId = $firstStage?->id;
            }
            
            // Create the submission
            $submission = Submission::create([
                'id' => $data['id'] ?? Str::uuid(),
                'title' => $data['title'],
                'submission_type_id' => $data['submission_type_id'],
                'current_stage_id' => $currentStageId,
                'status' => $data['status'] ?? 'draft',
                'user_id' => $data['user_id'],
            ]);
            
            // Create type-specific detail record if needed
            $this->createTypeSpecificDetails($submission, $data);
            
            // Attach documents
            if (!empty($documents)) {
                $this->attachDocuments($submission, $documents);
            }
            
            return $submission;
        });
    }
    
    /**
     * Create type-specific detail record based on submission type
     */
    protected function createTypeSpecificDetails(Submission $submission, array $data): void
    {
        $typeSlug = $submission->submissionType->slug ?? null;
        
        switch ($typeSlug) {
            case 'paten':
                $submission->patentDetail()->create([
                    'patent_type' => $data['patent_type'] ?? 'utility',
                    'inventor_details' => $data['inventor_details'] ?? null,
                    'invention_description' => $data['invention_description'] ?? null,
                    'technical_field' => $data['technical_field'] ?? null,
                ]);
                break;
                
            case 'brand':
                $submission->trademarkDetail()->create([
                    'trademark_type' => $data['trademark_type'] ?? 'word',
                    'goods_services' => $data['goods_services'] ?? null,
                    'class_numbers' => $data['class_numbers'] ?? null,
                ]);
                break;
                
            case 'haki':
                $submission->copyrightDetail()->create([
                    'copyright_type' => $data['copyright_type'] ?? 'literary',
                    'author_details' => $data['author_details'] ?? null,
                    'creation_date' => $data['creation_date'] ?? null,
                    'first_publication' => $data['first_publication'] ?? null,
                ]);
                break;
                
            case 'industrial_design':
                $submission->industrialDesignDetail()->create([
                    'design_type' => $data['design_type'] ?? 'product',
                    'designer_details' => $data['designer_details'] ?? null,
                    'product_description' => $data['product_description'] ?? null,
                ]);
                break;
                
            // Handle new submission types here
            case 'plant_variety':
                $submission->plantVarietyDetail()->create([
                    'variety_name' => $data['variety_name'] ?? null,
                    'botanical_taxon' => $data['botanical_taxon'] ?? null,
                    'breeder_details' => $data['breeder_details'] ?? null,
                ]);
                break;
                
            case 'geographical_indication':
                $submission->geographicalIndicationDetail()->create([
                    'product_name' => $data['product_name'] ?? null,
                    'geographical_area' => $data['geographical_area'] ?? null,
                    'product_qualities' => $data['product_qualities'] ?? null,
                ]);
                break;
        }
    }
    
    /**
     * Attach documents to a submission
     */
    protected function attachDocuments(Submission $submission, array $documents): void
    {
        $submissionType = $submission->submissionType;
        $documentRequirements = $submissionType->documentRequirements;
        
        foreach ($documents as $document) {
            // Find the requirement if a requirement_id is specified
            $requirementId = $document['requirement_id'] ?? null;
            
            $submissionDocument = $submission->submissionDocuments()->create([
                'document_id' => $document['document_id'],
                'requirement_id' => $requirementId,
                'status' => $document['status'] ?? 'pending',
                'notes' => $document['notes'] ?? null,
            ]);
        }
    }
    
    /**
     * Advance a submission to the next workflow stage
     */
    public function advanceSubmission(Submission $submission, array $data = []): Submission
    {
        return DB::transaction(function () use ($submission, $data) {
            $currentStage = $submission->currentStage;
            
            if (!$currentStage) {
                throw new \Exception('Submission does not have a current stage.');
            }
            
            // Get the next stage in the workflow
            $nextStage = $currentStage->nextStage();
            
            if (!$nextStage) {
                // This is the final stage
                $submission->update([
                    'status' => 'completed',
                    'certificate' => $data['certificate'] ?? strtoupper(Str::random(8)),
                ]);
                
                // Create tracking history entry
                $submission->trackingHistory()->create([
                    'stage_id' => $currentStage->id,
                    'status' => 'completed',
                    'comment' => $data['comment'] ?? 'Submission completed successfully.',
                    'processed_by' => $data['processed_by'] ?? null,
                ]);
            } else {
                // Update to the next stage
                $submission->update([
                    'current_stage_id' => $nextStage->id,
                    'status' => 'in_review',
                ]);
                
                // Create tracking history entry
                $submission->trackingHistory()->create([
                    'stage_id' => $nextStage->id,
                    'status' => 'in_progress',
                    'comment' => $data['comment'] ?? "Advanced to {$nextStage->name} stage.",
                    'processed_by' => $data['processed_by'] ?? null,
                ]);
            }
            
            return $submission->fresh(['currentStage', 'trackingHistory']);
        });
    }
}
