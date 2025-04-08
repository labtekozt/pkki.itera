<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\PatentDetail;
use App\Models\SubmissionType;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSubmission extends CreateRecord
{
    protected static string $resource = SubmissionResource::class;
    protected array $patentData = [];
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Make sure user_id is set if not already
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        
        // Extract type-specific data for proper handling
        $submissionTypeId = $data['submission_type_id'] ?? null;
        if ($submissionTypeId) {
            $submissionType = SubmissionType::find($submissionTypeId);
            
            if ($submissionType?->slug === 'paten') {
                // Move patent-specific data to the PatentDetail relationship
                $patentData = [];
                
                if (isset($data['patentDetail'])) {
                    // If we're already using the new structure with a patentDetail key
                    $patentData = $data['patentDetail'];
                    unset($data['patentDetail']);
                } else {
                    // If using old structure, move data to the right place
                    if (isset($data['inventor_details'])) {
                        $patentData['inventor_details'] = $data['inventor_details'];
                        unset($data['inventor_details']);
                    }
                    
                    if (isset($data['metadata'])) {
                        $patentData['invention_description'] = $data['metadata']['invention_type'] ?? '';
                        $patentData['technical_field'] = $data['metadata']['technology_field'] ?? null;
                        unset($data['metadata']);
                    }
                }
                
                // Store temporary data for post-creation handling
                $this->patentData = $patentData;
            }
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Handle related models after creating the submission
        if (isset($this->patentData) && !empty($this->patentData)) {
            $this->patentData['submission_id'] = $this->record->id;
            PatentDetail::create($this->patentData);
        }
    }
}
