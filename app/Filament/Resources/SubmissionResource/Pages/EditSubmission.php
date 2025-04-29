<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Forms\SubmissionFormFactory;
use App\Filament\Resources\SubmissionResource;
use App\Models\SubmissionType;
use App\Models\Document;
use App\Models\SubmissionDocument;
use App\Services\SubmissionService;
use App\Services\WorkflowService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    public $uploadedDocuments = [];
    public $documentValidationStatus = [];
    public $isDocumentComplete = false;
    public $stageRequirementsSatisfied = false;

    /**
     * Generate a properly formatted URI for document storage
     * 
     * @param string|int $requirementId The requirement ID
     * @param string $filename The filename to use
     * @return string The formatted URI path
     */
    protected function formatDocumentUri($requirementId, $filename): string
    {
        return "submissions/{$this->record->id}/{$requirementId}/{$filename}";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Initialize document validation status
        $this->uploadedDocuments = [];
        $this->documentValidationStatus = [];
        $this->isDocumentComplete = false;
        $this->stageRequirementsSatisfied = false;

        // Check document requirements immediately
        $this->checkDocumentRequirements();

        // Process type-specific details
        $submissionType = $this->record->submissionType;

        if ($submissionType) {
            switch ($submissionType->slug) {
                case 'paten':
                    if ($this->record->patentDetail) {
                        $data['patentDetail'] = $this->record->patentDetail->toArray();
                    }
                    break;
                case 'brand':
                    if ($this->record->brandDetail) {
                        $data['brandDetail'] = $this->record->brandDetail->toArray();
                    }
                    break;
                case 'haki':
                    if ($this->record->hakiDetail) {
                        $data['hakiDetail'] = $this->record->hakiDetail->toArray();
                    }
                    break;
                case 'industrial_design':
                    if ($this->record->industrialDesignDetail) {
                        $data['industrialDesignDetail'] = $this->record->industrialDesignDetail->toArray();
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Check if all required documents have been uploaded
     */
    public function checkDocumentRequirements(): void
    {
        if (!$this->record || !$this->record->submissionType) {
            $this->isDocumentComplete = false;
            return;
        }

        $requirements = $this->record->submissionType->documentRequirements()
            ->where('required', true)
            ->get();

        if ($requirements->isEmpty()) {
            $this->isDocumentComplete = true;
            return;
        }

        $missingDocuments = [];
        $this->documentValidationStatus = [];


        foreach ($requirements as $requirement) {
            $document = $this->record->submissionDocuments()
                ->where('requirement_id', $requirement->id)
                ->where('status', '!=', 'replaced')
                ->latest()
                ->first();

            if (!$document) {
                $missingDocuments[] = $requirement->name;
                $this->documentValidationStatus[$requirement->id] = [
                    'status' => 'missing',
                    'message' => 'Required document is missing'
                ];
            } else {
                $this->documentValidationStatus[$requirement->id] = [
                    'status' => 'uploaded',
                    'document_status' => $document->status,
                    'document_id' => $document->id
                ];
            }
        }

        $this->isDocumentComplete = empty($missingDocuments);
    }

    /**
     * Process document uploads when form is saved
     */
    protected function processDocumentUploads(array $formData): void
    {
        // Check if there are documents to process
        if (isset($formData['documents']) && is_array($formData['documents'])) {
            foreach ($formData['documents'] as $requirementId => $uploadedFile) {
                if (!$uploadedFile) continue;

                $filename = $uploadedFile instanceof TemporaryUploadedFile ?
                    $uploadedFile->getClientOriginalName() : basename($uploadedFile);

                $document = Document::create([
                    'title' => $filename,
                    'uri' => $this->formatDocumentUri($requirementId, $filename),
                    'mimetype' => $uploadedFile instanceof TemporaryUploadedFile ? $uploadedFile->getMimeType() : Storage::disk('public')->mimeType($uploadedFile),
                    'size' => $uploadedFile instanceof TemporaryUploadedFile ? $uploadedFile->getSize() : Storage::disk('public')->size($uploadedFile),
                ]);

                // Move file from temporary storage to permanent storage
                if ($uploadedFile instanceof TemporaryUploadedFile) {
                    // Get the proper URI for storage
                    $uri = $this->formatDocumentUri($requirementId, $uploadedFile->getClientOriginalName());

                    // Store the file using public disk
                    Storage::disk('public')->put(
                        $uri,
                        file_get_contents($uploadedFile->getRealPath())
                    );

                    // Update document record with correct URI
                    $document->update(['uri' => $uri]);
                }

                // Create the submission document record
                $this->record->submissionDocuments()->create([
                    'document_id' => $document->id,
                    'requirement_id' => $requirementId,
                    'status' => 'pending',
                ]);
            }

            // Update document validation status
            $this->checkDocumentRequirements();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Submission Information')
                    ->schema([
                        Hidden::make('user_id')
                            ->default(fn() => $this->record->user_id),

                        Select::make('submission_type_id')
                            ->relationship('submissionType', 'name')
                            ->required()
                            ->disabled()
                            ->helperText('Submission type cannot be changed after creation')
                            ->default(fn() => $this->record->submission_type_id)
                            ->dehydrated(false),

                        TextInput::make('title')
                            ->required()
                            ->helperText('Clear and concise title describing the submission')
                            ->maxLength(255)
                            ->columnSpanFull(),


                        Section::make('Type Details')
                            ->schema(function () {
                                $submissionType = $this->record->submissionType;

                                if (!$submissionType) {
                                    return [
                                        Placeholder::make('no_type')
                                            ->content('No submission type associated with this record')
                                            ->columnSpanFull(),
                                    ];
                                }

                                return SubmissionFormFactory::getFormForSubmissionType($submissionType->slug);
                            }),

                        Section::make('Document Requirements')
                            ->schema(function () {
                                if (!$this->record->submissionType) {
                                    return [
                                        Placeholder::make('no_type')
                                            ->content('No submission type associated with this record')
                                            ->columnSpanFull(),
                                    ];
                                }

                                $requirements = $this->record->submissionType->documentRequirements()->get();

                                if ($requirements->isEmpty()) {
                                    return [
                                        Placeholder::make('no_requirements')
                                            ->content('No document requirements defined for this submission type')
                                            ->columnSpanFull(),
                                    ];
                                }

                                $fields = [];

                                // Add document validation status at the top of the section
                                $fields[] = Placeholder::make('document_validation')
                                    ->content(function () {
                                        $this->checkDocumentRequirements();

                                        if ($this->isDocumentComplete) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-4 bg-green-50 border border-green-200 rounded-xl mb-4">
                                            <div class="flex items-center">
                                                <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="font-medium text-green-800">All required documents have been uploaded</span>
                                            </div>
                                            <p class="text-green-700 mt-1">You can proceed with submission</p>
                                        </div>'
                                            );
                                        }

                                        $requirements = $this->record->submissionType->documentRequirements()
                                            ->where('required', true)
                                            ->get();

                                        $content = '<div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-4">
                                            <div class="flex items-center">
                                                <svg class="h-5 w-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="font-medium text-yellow-800">Missing required documents</span>
                                            </div>
                                            <p class="text-yellow-700 mt-1">Please upload the following required documents:</p>
                                            <ul class="list-disc list-inside mt-2 space-y-1">';

                                        foreach ($requirements as $requirement) {
                                            if (
                                                !isset($this->documentValidationStatus[$requirement->id]) ||
                                                $this->documentValidationStatus[$requirement->id]['status'] === 'missing'
                                            ) {
                                                $content .= "<li class='text-yellow-700'>{$requirement->name}</li>";
                                            }
                                        }

                                        $content .= '</ul></div>';

                                        return new \Illuminate\Support\HtmlString($content);
                                    })
                                    ->columnSpanFull()
                                    ->visible(fn() => $this->record->status === 'draft');

                                // Document requirements table
                                foreach ($requirements as $requirement) {
                                    $existingDocs = $this->record->submissionDocuments()
                                        ->where('requirement_id', $requirement->id)
                                        ->with('document')
                                        ->latest()
                                        ->get();

                                    $fields[] = Section::make($requirement->name)
                                        ->description($requirement->description)
                                        ->extraAttributes([
                                            'class' => $requirement->required ? 'border-l-4 border-primary-500' : '',
                                        ])
                                        ->schema([
                                            Placeholder::make('requirement_info')
                                                ->content(function () use ($requirement, $existingDocs) {
                                                    $content = "";

                                                    if ($requirement->required) {
                                                        $content .= "<span class='text-primary-500 font-medium'>Required</span><br>";
                                                    } else {
                                                        $content .= "<span class='text-gray-500'>Optional</span><br>";
                                                    }

                                                    if ($existingDocs->count() > 0) {
                                                        $content .= "<div class='mt-2 space-y-2'>";
                                                        foreach ($existingDocs as $docItem) {
                                                            $statusColor = match ($docItem->status) {
                                                                'pending' => 'gray',
                                                                'approved' => 'success',
                                                                'rejected' => 'danger',
                                                                'revision_needed' => 'warning',
                                                                default => 'gray',
                                                            };

                                                            $downloadUrl = route('filament.admin.documents.download', $docItem->document_id);

                                                            $content .= "<div class='flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded'>";
                                                            $content .= "<div>";
                                                            $content .= "<div class='text-sm font-medium'>{$docItem->document->title}</div>";
                                                            $content .= "<div class='text-xs text-gray-500'>{$docItem->document->mimetype} - " .
                                                                number_format($docItem->document->size / 1024, 0) . " KB</div>";
                                                            $content .= "</div>";
                                                            $content .= "<div class='flex items-center space-x-2'>";
                                                            $content .= "<span class='px-2 py-1 text-xs rounded-full bg-{$statusColor}-100 text-{$statusColor}-800'>{$docItem->status}</span>";
                                                            $content .= "<a href='{$downloadUrl}' target='_blank' class='text-primary-600 hover:text-primary-800 text-xs'>Download</a>";
                                                            $content .= "</div>";
                                                            $content .= "</div>";
                                                        }
                                                        $content .= "</div>";
                                                    }

                                                    return new \Illuminate\Support\HtmlString($content);
                                                }),

                                            FileUpload::make("documents.{$requirement->id}")
                                                ->label('Upload New Document')
                                                ->acceptedFileTypes(
                                                    $requirement->allowed_file_types ?
                                                        array_map(fn($type) => ".$type", explode(',', $requirement->allowed_file_types)) :
                                                        ['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
                                                )
                                                ->preserveFilenames()
                                                ->maxSize($requirement->max_file_size ?? 10240)
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) use ($requirement) {
                                                    if (!$state) return;

                                                    $filename = $state instanceof TemporaryUploadedFile ?
                                                        $state->getClientOriginalName() : basename($state);

                                                    // Create document record with proper URI handling
                                                    $document = Document::create([
                                                        'title' => $filename,
                                                        'uri' => $this->formatDocumentUri($requirement->id, $filename),
                                                        'mimetype' => $state instanceof TemporaryUploadedFile ? $state->getMimeType() : Storage::disk('public')->mimeType($state),
                                                        'size' => $state instanceof TemporaryUploadedFile ? $state->getSize() : Storage::disk('public')->size($state),
                                                    ]);

                                                    // Move file from temporary storage to permanent storage
                                                    if ($state instanceof TemporaryUploadedFile) {
                                                        // Get the proper URI for storage
                                                        $uri = $this->formatDocumentUri($requirement->id, $state->getClientOriginalName());

                                                        // Store the file using public disk
                                                        Storage::disk('public')->put(
                                                            $uri,
                                                            file_get_contents($state->getRealPath())
                                                        );

                                                        // Update document record with correct URI
                                                        $document->update(['uri' => $uri]);
                                                    }

                                                    // Create submission document relationship
                                                    $this->record->submissionDocuments()->create([
                                                        'document_id' => $document->id,
                                                        'requirement_id' => $requirement->id,
                                                        'status' => 'pending',
                                                    ]);

                                                    // Clear the upload field to indicate successful upload
                                                    $set("documents.{$requirement->id}", null);

                                                    // Update validation status
                                                    $this->checkDocumentRequirements();

                                                    // Show notification
                                                    Notification::make()
                                                        ->title('Document uploaded successfully')
                                                        ->success()
                                                        ->send();
                                                }),
                                        ])
                                        ->collapsible();
                                }

                                return $fields;
                            }),

                        Section::make('Status Management')
                            ->schema([
                                Placeholder::make('current_status')
                                    ->content(function () {
                                        $status = $this->record->status;
                                        $statusColor = match ($status) {
                                            'draft' => 'gray',
                                            'submitted' => 'info',
                                            'in_review' => 'warning',
                                            'revision_needed' => 'danger',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'completed' => 'success',
                                            'cancelled' => 'gray',
                                            default => 'gray',
                                        };

                                        $content = "<div class='p-4 bg-{$statusColor}-50 border border-{$statusColor}-200 rounded-xl'>";
                                        $content .= "<h3 class='text-lg font-medium text-{$statusColor}-700'>Current Status: " . ucfirst(str_replace('_', ' ', $status)) . "</h3>";

                                        // Display current stage with more emphasis
                                        if ($this->record->currentStage) {
                                            $content .= "<div class='mt-2 flex items-center'>";
                                            $content .= "<span class='font-medium text-{$statusColor}-600 mr-2'>Current Stage:</span>";
                                            $content .= "<span class='px-3 py-1 text-sm bg-{$statusColor}-100 rounded-full font-medium text-{$statusColor}-800'>{$this->record->currentStage->name}</span>";
                                            $content .= "</div>";

                                            // Add stage description if available
                                            if (!empty($this->record->currentStage->description)) {
                                                $content .= "<p class='mt-1 text-sm text-{$statusColor}-600'>{$this->record->currentStage->description}</p>";
                                            }
                                        } else {
                                            $content .= "<p class='mt-2 text-{$statusColor}-600 italic'>No workflow stage assigned</p>";
                                        }

                                        $content .= "</div>";

                                        return new \Illuminate\Support\HtmlString($content);
                                    }),
                            ])
                            ->columns(2),
                        Select::make('status')
                            ->options(function () {
                                $options = [
                                    'draft' => 'Draft - Save for later editing',
                                    'cancelled' => 'Cancelled - Process terminated',
                                ];

                                if (($this->isDocumentComplete || $this->record->status !== 'draft') &&
                                    in_array($this->record->status, ['draft', 'cancelled'])
                                ) {
                                    $options['submitted'] = 'Submitted - Ready for review';
                                }
                                
                                // Add resubmit option when status is revision_needed
                                if ($this->record->status === 'revision_needed' && $this->isDocumentComplete) {
                                    $options['submitted'] = 'Resubmit - Send back for review';
                                }

                                return $options;
                            })
                            ->default(fn() => $this->record->status)
                            ->required()
                            ->reactive()
                            ->disabled(function () {
                                // Allow status change if status is draft, cancelled, or revision_needed
                                return !in_array($this->record->status, ['draft', 'cancelled', 'revision_needed']);
                            })
                            ->afterStateUpdated(function (string $state, Set $set) {
                                if ($state === 'submitted' && $this->record->status === 'draft') {
                                    if ($this->record->submissionType && !$this->record->current_stage_id) {
                                        $firstStage = $this->record->submissionType->firstStage();
                                        if ($firstStage) {
                                            $set('current_stage_id', $firstStage->id);
                                        }
                                    }

                                    $set('status_notes', 'Submission ready for review');
                                } else if ($state === 'submitted' && $this->record->status === 'revision_needed') {
                                    $set('status_notes', 'Submission updated and resubmitted for review');
                                }
                            })
                            ->helperText(function () {
                                if (!in_array($this->record->status, ['draft', 'cancelled', 'revision_needed'])) {
                                    return 'Status cannot be changed once submitted';
                                }

                                if ($this->record->status === 'draft' && !$this->isDocumentComplete) {
                                    return 'You need to upload all required documents before submitting';
                                }
                                
                                if ($this->record->status === 'revision_needed') {
                                    return 'After making the required changes, you can resubmit your application for review';
                                }
                                
                                return 'Changing status may trigger workflow actions';
                            }),

                        Textarea::make('reviewer_notes')
                            ->label('Reviewer Notes')
                            ->placeholder('notes about the submission')
                            ->helperText('These notes will be visible to the submitter when revision is needed or submission is rejected')
                            ->rows(3)
                            ->disabled(function () {
                                // Disable the reviewer notes field if current status is not revision_needed or rejected
                                return !in_array($this->record->status, ['revision_needed', 'rejected']);
                            }),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['processed_by'] = Auth::id();

        $comment = '';
        if (!empty($data['status_notes'])) {
            $comment = $data['status_notes'];
            unset($data['status_notes']);
        } elseif (!empty($data['comment'])) {
            $comment = $data['comment'];
        } elseif (isset($data['status']) && $data['status'] !== $this->record->status) {
            $comment = "Status changed from {$this->record->status} to {$data['status']}";
        }

        $data['comment'] = $comment;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $submissionService = app(SubmissionService::class);
        $documents = [];

        try {
            // Process any document uploads that weren't processed via live updates
            $this->processDocumentUploads($data);

            // Re-check requirements after possible uploads
            $this->checkDocumentRequirements();

            if (isset($data['status']) && $data['status'] === 'submitted' && $record->status === 'draft') {
                if (!$this->isDocumentComplete) {
                    Notification::make()
                        ->title('Cannot submit incomplete application')
                        ->body('Please upload all required documents before submitting')
                        ->danger()
                        ->send();

                    return $record;
                }

                if (!isset($data['current_stage_id']) && $record->submissionType) {
                    $firstStage = $record->submissionType->firstStage();
                    if ($firstStage) {
                        $data['current_stage_id'] = $firstStage->id;
                    }
                }
            }

            // Special handling for resubmission from revision_needed
            $wasRevisionNeeded = $record->status === 'revision_needed';

            $updatedRecord = $submissionService->updateSubmission(
                $record,
                $data,
                documents: $documents
            );

            // Handle notifications based on status change
            if (isset($data['status']) && $data['status'] === 'submitted') {
                if ($record->status === 'draft') {
                    Notification::make()
                        ->title('Submission successfully sent for review')
                        ->body('Your submission has been received and is now in the review process')
                        ->success()
                        ->send();
                } else if ($record->status === 'revision_needed') {
                    // This is a resubmission after revisions
                    Notification::make()
                        ->title('Resubmission successful')
                        ->body('Your revised submission has been sent back to the reviewer')
                        ->success()
                        ->send();
                    
                    // Notify reviewers through workflow assignments
                    $this->notifyReviewers($updatedRecord);
                    
                    // Create tracking history entry for the resubmission
                    \App\Models\TrackingHistory::create([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'submission_id' => $updatedRecord->id,
                        'stage_id' => $updatedRecord->current_stage_id,
                        'action' => 'resubmitted',
                        'status' => 'in_review',
                        'comment' => $data['comment'] ?? 'Submission resubmitted after revision',
                        'processed_by' => Auth::id(),
                        'source_status' => 'revision_needed',
                        'target_status' => 'submitted',
                        'event_type' => 'status_change',
                        'metadata' => [
                            'user_role' => Auth::user()->roles->pluck('name')->first() ?? 'submitter',
                        ],
                        'event_timestamp' => now(),
                    ]);
                }
            } else {
                Notification::make()
                    ->title('Submission updated successfully')
                    ->success()
                    ->send();
            }

            return $updatedRecord;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating submission')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return $record;
        }
    }
    
    /**
     * Notify all relevant reviewers when a submission is resubmitted
     */
    protected function notifyReviewers(Model $record): void
    {
        // Find the latest reviewer assignment for this submission
        $latestAssignments = $record->currentStageAssignments()
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($latestAssignments->isEmpty()) {
            return;
        }
        
        // Get all reviewers who should be notified
        $reviewerIds = $latestAssignments->pluck('reviewer_id')->unique();
        
        // Set all current assignments back to pending
        foreach ($latestAssignments as $assignment) {
            $assignment->update([
                'status' => 'pending',
                'completed_at' => null,
            ]);
        }
        
        // Get user information for the notification
        $submitter = Auth::user();
        $submitterName = $submitter ? $submitter->fullname ?? $submitter->name : 'A user';
        
        // Send notification to all reviewers
        foreach ($reviewerIds as $reviewerId) {
            $reviewer = \App\Models\User::find($reviewerId);
            if ($reviewer) {
                $reviewer->notify(new \App\Notifications\ReviewActionNotification(
                    $record,
                    "Submission resubmitted after revision",
                    "{$submitterName} has updated and resubmitted submission '{$record->title}' after making requested revisions."
                ));
            }
        }
    }
}
