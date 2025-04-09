<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionType;
use App\Models\TrackingHistory;
use App\Models\WorkflowStage;
use App\Models\User;
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
            
            // Create tracking history record for the initial submission
            if ($currentStageId) {
                $this->createTrackingHistory($submission, [
                    'action' => 'create',
                    'comment' => 'Submission created',
                    'processed_by' => $data['user_id'] ?? null,
                ]);
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
                if (isset($data['patentDetail'])) {
                    // Handle nested structure from Filament form
                    $detailData = [
                        'application_type' => $data['patentDetail']['patent_type'] ?? 'simple_patent',
                        'patent_title' => $data['patentDetail']['patent_title'] ?? $submission->title,
                        'patent_description' => $data['patentDetail']['patent_description'] ?? '',
                        'from_grant_research' => $data['patentDetail']['from_grant_research'] ?? false,
                        'self_funded' => $data['patentDetail']['self_funded'] ?? false,
                        'media_link' => $data['patentDetail']['media_link'] ?? null,
                        'inventors_name' => $data['patentDetail']['inventor_details'] ?? null,
                    ];
                } else {
                    // Map from flat structure for API/non-Filament uses
                    $detailData = [
                        'application_type' => $data['application_type'] ?? $data['patent_type'] ?? 'simple_patent',
                        'patent_title' => $data['patent_title'] ?? $data['title'] ?? $submission->title,
                        'patent_description' => $data['patent_description'] ?? $data['invention_description'] ?? '',
                        'from_grant_research' => $data['from_grant_research'] ?? false,
                        'self_funded' => $data['self_funded'] ?? false,
                        'media_link' => $data['media_link'] ?? null,
                        'inventors_name' => $data['inventors_name'] ?? $data['inventor_details'] ?? null,
                    ];
                }
                
                $submission->patentDetail()->create($this->filterData($detailData));
                break;
                
            case 'brand':
                // Handle trademark fields from Filament
                if (isset($data['trademarkDetail'])) {
                    $detailData = [
                        'brand_name' => $data['trademarkDetail']['trademark_name'] ?? $submission->title,
                        'brand_description' => $data['trademarkDetail']['description'] ?? null,
                        'inovators_name' => $data['trademarkDetail']['inventors_name'] ?? $data['user_id'] ?? null,
                        'application_type' => $data['trademarkDetail']['trademark_type'] ?? null,
                        'brand_type' => $data['trademarkDetail']['trademark_type'] ?? null,
                        'brand_label_description' => $data['trademarkDetail']['color_description'] ?? null,
                        'nice_classes' => $data['trademarkDetail']['nice_classes'] ?? null,
                        'goods_services_search' => $data['trademarkDetail']['goods_services_description'] ?? null,
                    ];
                } 
                // Handle brand fields from Filament
                elseif (isset($data['brandDetail'])) {
                    $detailData = $data['brandDetail'];
                } else {
                    // Map from flat structure
                    $detailData = [
                        'brand_name' => $data['brand_name'] ?? $data['trademark_name'] ?? $submission->title,
                        'brand_description' => $data['brand_description'] ?? $data['description'] ?? null,
                        'inovators_name' => $data['inovators_name'] ?? $data['inventor_details'] ?? null,
                        'application_type' => $data['application_type'] ?? null,
                        'application_date' => $data['application_date'] ?? null,
                        'application_origin' => $data['application_origin'] ?? null,
                        'application_category' => $data['application_category'] ?? null,
                        'brand_type' => $data['brand_type'] ?? $data['trademark_type'] ?? null,
                        'brand_label' => $data['brand_label'] ?? null,
                        'brand_label_reference' => $data['brand_label_reference'] ?? null,
                        'brand_label_description' => $data['brand_label_description'] ?? $data['color_description'] ?? null,
                        'brand_color_elements' => $data['brand_color_elements'] ?? null,
                        'foreign_language_translation' => $data['foreign_language_translation'] ?? null,
                        'disclaimer' => $data['disclaimer'] ?? null,
                        'priority_number' => $data['priority_number'] ?? null,
                        'nice_classes' => $data['nice_classes'] ?? null,
                        'goods_services_search' => $data['goods_services_search'] ?? $data['goods_services_description'] ?? null,
                    ];
                }
                
                $submission->brandDetail()->create($this->filterData($detailData));
                break;
                
            case 'haki':
                if (isset($data['copyrightDetail'])) {
                    // Map from Filament copyright form
                    $detailData = [
                        'work_type' => $data['copyrightDetail']['work_type'] ?? 'literary',
                        'work_subtype' => $data['copyrightDetail']['work_subtype'] ?? null,
                        'haki_title' => $data['copyrightDetail']['work_title'] ?? $submission->title,
                        'work_description' => $data['copyrightDetail']['work_description'] ?? null,
                        'first_publication_date' => $data['copyrightDetail']['publication_date'] ?? null,
                        'first_publication_place' => $data['copyrightDetail']['publication_place'] ?? null,
                        'is_kkn_output' => $data['copyrightDetail']['is_kkn_output'] ?? false,
                        'from_grant_research' => $data['copyrightDetail']['from_grant_research'] ?? false,
                        'self_funded' => $data['copyrightDetail']['self_funded'] ?? false,
                        'registration_number' => $data['copyrightDetail']['registration_number'] ?? null,
                        'registration_date' => $data['copyrightDetail']['registration_date'] ?? null,
                        'inventors_name' => $data['copyrightDetail']['authors'] ?? null,
                    ];
                }
                elseif (isset($data['hakiDetail'])) {
                    $detailData = $data['hakiDetail'];
                } else {
                    // Map from flat structure
                    $detailData = [
                        'work_type' => $data['work_type'] ?? $data['copyright_type'] ?? 'literary',
                        'work_subtype' => $data['work_subtype'] ?? null,
                        'haki_category' => $data['haki_category'] ?? null,
                        'haki_title' => $data['haki_title'] ?? $submission->title,
                        'work_description' => $data['work_description'] ?? $data['description'] ?? null,
                        'first_publication_date' => $data['first_publication_date'] ?? $data['publication_date'] ?? null,
                        'first_publication_place' => $data['first_publication_place'] ?? $data['publication_place'] ?? null,
                        'is_kkn_output' => $data['is_kkn_output'] ?? false,
                        'from_grant_research' => $data['from_grant_research'] ?? false,
                        'self_funded' => $data['self_funded'] ?? false,
                        'registration_number' => $data['registration_number'] ?? null,
                        'registration_date' => $data['registration_date'] ?? null,
                        'inventors_name' => $data['inventors_name'] ?? $data['authors'] ?? null,
                    ];
                }
                
                // Cast boolean fields properly
                foreach (['is_kkn_output', 'from_grant_research', 'self_funded'] as $boolField) {
                    if (isset($detailData[$boolField]) && is_string($detailData[$boolField])) {
                        $detailData[$boolField] = $detailData[$boolField] === 'true' || $detailData[$boolField] === '1';
                    }
                }
                
                $submission->hakiDetail()->create($this->filterData($detailData));
                break;
                
            case 'industrial_design':
                if (isset($data['industrialDesignDetail'])) {
                    // Map from Filament industrial design form to match database fields
                    $detailData = [
                        'design_title' => $data['industrialDesignDetail']['design_title'] ?? $submission->title,
                        'inventors_name' => $data['industrialDesignDetail']['designer_information'] ?? null,
                        'design_type' => $data['industrialDesignDetail']['design_type'] ?? null,
                        'design_description' => $data['industrialDesignDetail']['design_description'] ?? null,
                        'novelty_statement' => $data['industrialDesignDetail']['novelty_statement'] ?? null,
                        'designer_information' => $data['industrialDesignDetail']['designer_information'] ?? null,
                        'locarno_class' => $data['industrialDesignDetail']['locarno_class'] ?? null,
                    ];
                } else {
                    // Map from flat structure
                    $detailData = [
                        'design_title' => $data['design_title'] ?? $submission->title,
                        'inventors_name' => $data['inventors_name'] ?? $data['designer_information'] ?? null,
                        'design_type' => $data['design_type'] ?? null,
                        'design_description' => $data['design_description'] ?? null,
                        'novelty_statement' => $data['novelty_statement'] ?? null,
                        'designer_information' => $data['designer_information'] ?? null,
                        'locarno_class' => $data['locarno_class'] ?? null,
                    ];
                }
                
                $submission->industrialDesignDetail()->create($this->filterData($detailData));
                break;
                
            default:
                // For any other submission types, try a generic approach
                $relationMethod = Str::camel($typeSlug . '_detail'); 
                $detailKey = Str::camel($typeSlug . 'Detail');
                
                if (method_exists($submission, $relationMethod)) {
                    if (isset($data[$detailKey])) {
                        // Use the nested structure if available
                        $detailData = $data[$detailKey];
                    } else {
                        // Try to build detail data from flat structure
                        $detailData = array_filter($data, function($key) use ($typeSlug) {
                            return strpos($key, $typeSlug . '_') === 0 || 
                                   strpos($key, Str::snake($typeSlug) . '_') === 0;
                        }, ARRAY_FILTER_USE_KEY);
                    }
                    
                    if (!empty($detailData)) {
                        $submission->$relationMethod()->create($this->filterData($detailData));
                    }
                }
                break;
        }
    }
    
    /**
     * Filter data array without removing boolean false values
     * 
     * @param array $data
     * @return array
     */
    protected function filterData(array $data): array
    {
        return array_filter($data, function ($value) {
            // Keep everything except null values
            return !is_null($value);
        });
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
        // Make sure we have a current stage
        if (!$submission->currentStage) {
            throw new \Exception('Submission does not have a current stage');
        }

        // Check if all requirements are fulfilled
        if (!$this->checkStageRequirements($submission)) {
            throw new \Exception('Not all required documents have been approved for this stage');
        }

        // Get the next stage
        $nextStage = $submission->currentStage->nextStage();

        // Start a transaction
        return DB::transaction(function () use ($submission, $nextStage, $data) {
            // Create tracking history record
            $this->createTrackingHistory($submission, $data);

            // If there's no next stage, mark the submission as completed
            if (!$nextStage) {
                $submission->update([
                    'status' => 'completed',
                    'certificate' => $data['certificate'] ?? strtoupper(Str::random(8)),
                ]);
                return $submission;
            }

            // Update the submission with the new stage
            $submission->update([
                'current_stage_id' => $nextStage->id,
                'status' => 'in_review',
            ]);

            return $submission->fresh(['currentStage', 'trackingHistory']);
        });
    }

    /**
     * Move a submission back to the previous stage
     */
    public function revertSubmission(Submission $submission, array $options = []): Submission
    {
        // Make sure we have a current stage
        if (!$submission->currentStage) {
            throw new \Exception('Submission does not have a current stage');
        }

        // Get the previous stage
        $previousStage = $submission->currentStage->previousStage();

        if (!$previousStage) {
            throw new \Exception('This is the first stage, cannot revert further');
        }

        // Start a transaction
        return DB::transaction(function () use ($submission, $previousStage, $options) {
            // Create tracking history record
            $this->createTrackingHistory(
                $submission,
                array_merge($options, ['action' => 'revert'])
            );

            // Update the submission with the previous stage
            $submission->update([
                'current_stage_id' => $previousStage->id,
                'status' => 'revision_needed',
            ]);

            return $submission;
        });
    }

    /**
     * Create a tracking history record for a submission
     */
    protected function createTrackingHistory(Submission $submission, array $options = []): TrackingHistory
    {
        $action = $options['action'] ?? 'advance';
        $processedBy = $options['processed_by'] ?? null;
        
        $trackingData = [
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'action' => $action,
            'status' => (string) $submission->status, // Ensure status is cast as a string
            'processed_by' => $processedBy,
            'comment' => $options['comment'] ?? null,
        ];

        // Add metadata if available
        if (isset($options['metadata'])) {
            $trackingData['metadata'] = $options['metadata'];
        }

        return TrackingHistory::create($trackingData);
    }

    /**
     * Check if a submission meets all requirements for the current stage
     */
    public function checkStageRequirements(Submission $submission): bool
    {
        if (!$submission->currentStage) {
            return false;
        }

        return $submission->currentStage->areRequirementsFulfilled($submission);
    }

    /**
     * Update a submission document status
     */
    public function updateDocumentStatus(SubmissionDocument $document, string $status, ?string $notes = null, ?int $processedBy = null): SubmissionDocument
    {
        $document->update([
            'status' => $status,
            'notes' => $notes ?? $document->notes,
        ]);

        // Create tracking history for document status update
        TrackingHistory::create([
            'submission_id' => $document->submission_id,
            'stage_id' => $document->submission->current_stage_id,
            'action' => 'document_update',
            'status' => $status,
            'processed_by' => $processedBy,
            'comment' => "Document '{$document->requirement->name}' status updated to {$status}",
            'metadata' => [
                'document_id' => $document->id,
                'requirement_id' => $document->requirement_id,
                'notes' => $notes,
            ],
        ]);

        return $document;
    }

    /**
     * Check if all required documents are approved for the current stage
     */
    public function getDocumentsFulfillmentStatus(Submission $submission): array
    {
        if (!$submission->currentStage) {
            return [
                'fulfilled' => false,
                'total_required' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0,
                'missing' => 0,
            ];
        }

        // Get all required document requirements for this stage
        $stageRequirements = $submission->currentStage->documentRequirements()
            ->wherePivot('is_required', true)
            ->get();
        
        $totalRequired = $stageRequirements->count();
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        $missingRequirements = [];

        foreach ($stageRequirements as $requirement) {
            // Find the latest document for this requirement
            $document = $submission->submissionDocuments()
                ->where('requirement_id', $requirement->id)
                ->where('status', '!=', 'replaced')
                ->latest()
                ->first();
            
            if (!$document) {
                $missingRequirements[] = $requirement->id;
            } else if ($document->status === 'approved') {
                $approvedCount++;
            } else if ($document->status === 'rejected') {
                $rejectedCount++;
            } else {
                $pendingCount++;
            }
        }

        return [
            'fulfilled' => $approvedCount === $totalRequired,
            'total_required' => $totalRequired,
            'approved' => $approvedCount,
            'pending' => $pendingCount,
            'rejected' => $rejectedCount,
            'missing' => count($missingRequirements),
            'missing_requirements' => $missingRequirements,
        ];
    }

    /**
     * Get the appropriate stage based on submission type and criteria
     */
    public function getAppropriateStage(string $submissionTypeId, array $criteria = []): ?WorkflowStage
    {
        $submissionType = SubmissionType::find($submissionTypeId);
        
        if (!$submissionType) {
            return null;
        }
        
        // Default to first stage if no criteria provided
        if (empty($criteria)) {
            return $submissionType->firstStage();
        }
        
        // If stage code is provided, find the stage by code
        if (isset($criteria['code'])) {
            return $submissionType->workflowStages()
                ->where('code', $criteria['code'])
                ->where('is_active', true)
                ->first();
        }
        
        // If order is provided, find the stage by order
        if (isset($criteria['order'])) {
            return $submissionType->workflowStages()
                ->where('order', $criteria['order'])
                ->where('is_active', true)
                ->first();
        }
        
        return $submissionType->firstStage();
    }
}
