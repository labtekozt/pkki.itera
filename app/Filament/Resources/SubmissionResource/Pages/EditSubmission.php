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
                // Consider rejected and revision_needed documents as needing replacement
                if (in_array($document->status, ['rejected', 'revision_needed'])) {
                    $missingDocuments[] = $requirement->name;
                    $this->documentValidationStatus[$requirement->id] = [
                        'status' => 'needs_replacement',
                        'document_status' => $document->status,
                        'document_id' => $document->id,
                        'message' => $document->status === 'rejected' 
                            ? 'Document was rejected and needs to be replaced'
                            : 'Document needs revision and should be replaced'
                    ];
                } elseif (in_array($document->status, ['approved', 'final'])) {
                    $this->documentValidationStatus[$requirement->id] = [
                        'status' => 'satisfied',
                        'document_status' => $document->status,
                        'document_id' => $document->id,
                        'message' => 'Document approved'
                    ];
                } else {
                    // Status is 'pending' or other
                    $this->documentValidationStatus[$requirement->id] = [
                        'status' => 'uploaded',
                        'document_status' => $document->status,
                        'document_id' => $document->id,
                        'message' => 'Document uploaded, pending review'
                    ];
                }
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
                                            $validationInfo = $this->documentValidationStatus[$requirement->id] ?? null;
                                            
                                            if (!$validationInfo || in_array($validationInfo['status'], ['missing', 'needs_replacement'])) {
                                                $icon = '📄';
                                                $message = $requirement->name;
                                                
                                                if ($validationInfo && $validationInfo['status'] === 'needs_replacement') {
                                                    $icon = $validationInfo['document_status'] === 'rejected' ? '❌' : '📝';
                                                    $message .= " - " . $validationInfo['message'];
                                                }
                                                
                                                $content .= "<li class='text-yellow-700'>{$icon} {$message}</li>";
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
                                        ->where('status', '!=', 'replaced')
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
                                                                'pending' => 'blue',
                                                                'approved' => 'green',
                                                                'rejected' => 'red',
                                                                'revision_needed' => 'yellow',
                                                                'final' => 'emerald',
                                                                default => 'gray',
                                                            };
                                                            
                                                            $statusIcon = match ($docItem->status) {
                                                                'pending' => '⏳',
                                                                'approved' => '✅',
                                                                'rejected' => '❌',
                                                                'revision_needed' => '📝',
                                                                'final' => '🎯',
                                                                default => '📄',
                                                            };

                                                            $downloadUrl = route('filament.admin.documents.download', $docItem->document_id);
                                                            
                                                            $content .= "<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border'>";
                                            $content .= "<div class='flex items-center justify-between mb-2'>";
                                            $content .= "<div>";
                                            $content .= "<div class='text-sm font-medium'>{$docItem->document->title}</div>";
                                            $content .= "<div class='text-xs text-gray-500'>{$docItem->document->mimetype} - " .
                                                number_format($docItem->document->size / 1024, 0) . " KB</div>";
                                            $content .= "</div>";
                                            $content .= "<div class='flex items-center space-x-2'>";
                                            $content .= "<span class='px-2 py-1 text-xs rounded-full bg-{$statusColor}-100 text-{$statusColor}-800'>{$statusIcon} {$docItem->status}</span>";
                                            $content .= "<a href='{$downloadUrl}' target='_blank' class='text-primary-600 hover:text-primary-800 text-xs'>Download</a>";
                                            $content .= "</div>";
                                            $content .= "</div>";
                                            
                                            // Show existing reviewer notes if present with enhanced UI
                                            if (!empty($docItem->notes)) {
                                                $notesBgColor = $docItem->status === 'rejected' ? 'red' : ($docItem->status === 'revision_needed' ? 'amber' : 'blue');
                                                $notesIcon = $docItem->status === 'rejected' ? '❌' : ($docItem->status === 'revision_needed' ? '📝' : '💬');
                                                $notesBorderColor = $docItem->status === 'rejected' ? 'border-red-400' : ($docItem->status === 'revision_needed' ? 'border-amber-400' : 'border-blue-400');
                                                $notesTextColor = $docItem->status === 'rejected' ? 'text-red-800' : ($docItem->status === 'revision_needed' ? 'text-amber-800' : 'text-blue-800');
                                                $notesBadgeColor = $docItem->status === 'rejected' ? 'bg-red-100 text-red-800' : ($docItem->status === 'revision_needed' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800');
                                                
                                                $content .= "<div class='mt-4 relative'>";
                                                $content .= "<div class='bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border {$notesBorderColor} rounded-xl shadow-lg overflow-hidden'>";
                                                
                                                // Header with icon and status
                                                $content .= "<div class='bg-gradient-to-r from-{$notesBgColor}-50 to-{$notesBgColor}-100 dark:from-{$notesBgColor}-900/50 dark:to-{$notesBgColor}-800/50 px-5 py-3 border-b {$notesBorderColor}'>";
                                                $content .= "<div class='flex items-center justify-between'>";
                                                $content .= "<div class='flex items-center space-x-3'>";
                                                $content .= "<span class='text-2xl'>{$notesIcon}</span>";
                                                $content .= "<div>";
                                                $content .= "<h5 class='text-base font-semibold {$notesTextColor} dark:text-{$notesBgColor}-200'>Feedback dari Reviewer</h5>";
                                                $content .= "<p class='text-xs text-{$notesBgColor}-600 dark:text-{$notesBgColor}-400 mt-0.5'>Catatan untuk perbaikan dokumen</p>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                $content .= "<span class='inline-flex items-center px-3 py-1 text-xs font-medium rounded-full {$notesBadgeColor} shadow-sm'>";
                                                $content .= ucfirst(str_replace('_', ' ', $docItem->status));
                                                $content .= "</span>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                
                                                // Content area with better typography
                                                $content .= "<div class='p-5'>";
                                                $content .= "<div class='prose prose-sm max-w-none'>";
                                                $content .= "<div class='text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap text-sm bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700'>";
                                                $content .= e($docItem->notes);
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                
                                                // Footer with helpful info
                                                $content .= "<div class='mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400'>";
                                                $content .= "<svg class='w-4 h-4 mr-1.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
                                                $content .= "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>";
                                                $content .= "</svg>";
                                                $content .= "Harap perhatikan feedback ini saat mengganti dokumen";
                                                $content .= "</div>";
                                                
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                            }
                                            
                                            // Show action needed message for rejected/revision_needed documents with enhanced UI
                                            if (in_array($docItem->status, ['rejected', 'revision_needed'])) {
                                                $actionColor = $docItem->status === 'rejected' ? 'red' : 'amber';
                                                $actionIcon = $docItem->status === 'rejected' ? '🚨' : '⚠️';
                                                $actionTitle = $docItem->status === 'rejected' ? 'Dokumen Ditolak' : 'Perlu Revisi';
                                                $actionMessage = $docItem->status === 'rejected' 
                                                    ? 'Dokumen ini telah ditolak oleh reviewer. Silakan upload dokumen baru dengan perbaikan yang diperlukan.'
                                                    : 'Dokumen ini memerlukan revisi berdasarkan feedback reviewer. Silakan upload versi yang telah diperbaiki.';
                                                    
                                                $content .= "<div class='mt-3 p-4 bg-gradient-to-r from-{$actionColor}-100 to-{$actionColor}-50 dark:from-{$actionColor}-900/50 dark:to-{$actionColor}-900/20 border border-{$actionColor}-300 dark:border-{$actionColor}-700 rounded-lg shadow-sm'>";
                                                $content .= "<div class='flex items-start space-x-3'>";
                                                $content .= "<div class='flex-shrink-0'>";
                                                $content .= "<div class='w-8 h-8 bg-{$actionColor}-200 dark:bg-{$actionColor}-700 rounded-full flex items-center justify-center'>";
                                                $content .= "<span class='text-lg'>{$actionIcon}</span>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                $content .= "<div class='flex-1'>";
                                                $content .= "<h4 class='text-sm font-semibold text-{$actionColor}-800 dark:text-{$actionColor}-200 mb-1'>{$actionTitle}</h4>";
                                                $content .= "<p class='text-sm text-{$actionColor}-700 dark:text-{$actionColor}-300 leading-relaxed'>{$actionMessage}</p>";
                                                $content .= "<div class='mt-2 flex items-center text-xs text-{$actionColor}-600 dark:text-{$actionColor}-400'>";
                                                $content .= "<svg class='w-4 h-4 mr-1' fill='currentColor' viewBox='0 0 20 20'>";
                                                $content .= "<path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'></path>";
                                                $content .= "</svg>";
                                                $content .= "<span>Gunakan formulir upload di bawah untuk mengganti dokumen ini</span>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                                $content .= "</div>";
                                            }
                                            
                                            $content .= "</div>";
                                                        }
                                                        $content .= "</div>";
                                                    }

                                                    return new \Illuminate\Support\HtmlString($content);
                                                }),

                                            // Read-only reviewer notes display for each document (for admins only)
                                            ...($existingDocs->map(function ($docItem) use ($requirement) {
                                                // Only show this for admins to view existing feedback, but not edit
                                                if (!auth()->user()->hasAnyRole(['super_admin', 'admin']) || empty($docItem->notes)) {
                                                    return null;
                                                }

                                                return Section::make()
                                                    ->heading("👁️ Reviewer Feedback: {$docItem->document->title}")
                                                    ->description('Catatan reviewer yang telah diberikan (readonly - gunakan halaman Review untuk mengedit)')
                                                    ->schema([
                                                        Placeholder::make("document_notes_readonly.{$docItem->id}")
                                                            ->label('Catatan Reviewer')
                                                            ->content(function () use ($docItem) {
                                                                $statusColor = match ($docItem->status) {
                                                                    'rejected' => 'red',
                                                                    'revision_needed' => 'amber',
                                                                    default => 'blue'
                                                                };
                                                                
                                                                $content = "<div class='p-4 bg-{$statusColor}-50 dark:bg-{$statusColor}-900/20 border border-{$statusColor}-200 dark:border-{$statusColor}-700 rounded-lg'>";
                                                                $content .= "<div class='text-sm text-{$statusColor}-800 dark:text-{$statusColor}-200 whitespace-pre-wrap leading-relaxed'>";
                                                                $content .= e($docItem->notes);
                                                                $content .= "</div>";
                                                                $content .= "<div class='mt-3 pt-3 border-t border-{$statusColor}-200 dark:border-{$statusColor}-700'>";
                                                                $content .= "<div class='flex items-center text-xs text-{$statusColor}-600 dark:text-{$statusColor}-400'>";
                                                                $content .= "<svg class='w-4 h-4 mr-1.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
                                                                $content .= "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>";
                                                                $content .= "</svg>";
                                                                $content .= "💡 Untuk mengedit feedback ini, gunakan halaman Review Submission";
                                                                $content .= "</div>";
                                                                $content .= "</div>";
                                                                $content .= "</div>";
                                                                
                                                                return new \Illuminate\Support\HtmlString($content);
                                                            })
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columnSpanFull()
                                                    ->collapsible()
                                                    ->collapsed(false)
                                                    ->extraAttributes([
                                                        'class' => 'border-l-4 border-gray-400 bg-gray-50 dark:bg-gray-900/20'
                                                    ]);
                                            })->filter()->toArray()),

                                            // Read-only document status display for reviewers/admins
                                            ...($existingDocs->map(function ($docItem) use ($requirement) {
                                                // Only show status display for reviewers/admins
                                                if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
                                                    return null;
                                                }

                                                return Placeholder::make("document_status_readonly.{$docItem->id}")
                                                    ->label("Status: {$docItem->document->title}")
                                                    ->content(function () use ($docItem) {
                                                        $statusColor = match ($docItem->status) {
                                                            'pending' => 'blue',
                                                            'approved' => 'green',
                                                            'rejected' => 'red',
                                                            'revision_needed' => 'yellow',
                                                            'replaced' => 'gray',
                                                            'final' => 'emerald',
                                                            default => 'gray',
                                                        };
                                                        
                                                        $statusIcon = match ($docItem->status) {
                                                            'pending' => '⏳',
                                                            'approved' => '✅',
                                                            'rejected' => '❌',
                                                            'revision_needed' => '📝',
                                                            'replaced' => '🔄',
                                                            'final' => '🎯',
                                                            default => '📄',
                                                        };

                                                        $content = "<div class='inline-flex items-center px-3 py-2 bg-{$statusColor}-100 text-{$statusColor}-800 dark:bg-{$statusColor}-900/50 dark:text-{$statusColor}-200 rounded-lg border border-{$statusColor}-200 dark:border-{$statusColor}-700'>";
                                                        $content .= "<span class='text-sm font-medium'>{$statusIcon} " . ucfirst(str_replace('_', ' ', $docItem->status)) . "</span>";
                                                        $content .= "</div>";
                                                        $content .= "<div class='mt-2 text-xs text-gray-600 dark:text-gray-400'>";
                                                        $content .= "💡 Untuk mengubah status, gunakan halaman Review Submission";
                                                        $content .= "</div>";
                                                        
                                                        return new \Illuminate\Support\HtmlString($content);
                                                    });
                                            })->filter()->toArray()),

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

                                                    // Check if there are existing documents for this requirement that need to be replaced
                                                    $existingDocs = $this->record->submissionDocuments()
                                                        ->where('requirement_id', $requirement->id)
                                                        ->where('status', '!=', 'replaced')
                                                        ->get();
                                                    
                                                    // Mark existing rejected/revision_needed documents as replaced
                                                    foreach ($existingDocs as $existingDoc) {
                                                        if (in_array($existingDoc->status, ['rejected', 'revision_needed'])) {
                                                            $existingDoc->update(['status' => 'replaced']);
                                                        }
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

                                return $options;
                            })
                            ->default(fn() => $this->record->status)
                            ->required()
                            ->reactive()
                            ->disabled(function () {
                                // Disable the status field if current status is not draft or cancelled
                                return !in_array($this->record->status, ['draft', 'cancelled']);
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
                                }
                            })
                            ->helperText(function () {
                                if (!in_array($this->record->status, ['draft', 'cancelled'])) {
                                    return 'Status cannot be changed once submitted';
                                }

                                if ($this->record->status === 'draft' && !$this->isDocumentComplete) {
                                    return 'You need to upload all required documents before submitting';
                                }
                                return 'Changing status may trigger workflow actions';
                            }),

                        // Read-only reviewer notes display
                        Placeholder::make('reviewer_notes_readonly')
                            ->label('Reviewer Notes')
                            ->content(function () {
                                if (empty($this->record->reviewer_notes)) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-gray-500 italic">No reviewer notes available</div>'
                                    );
                                }
                                
                                $content = "<div class='p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg'>";
                                $content .= "<div class='text-sm text-blue-800 dark:text-blue-200 whitespace-pre-wrap leading-relaxed'>";
                                $content .= e($this->record->reviewer_notes);
                                $content .= "</div>";
                                $content .= "<div class='mt-3 pt-3 border-t border-blue-200 dark:border-blue-700'>";
                                $content .= "<div class='flex items-center text-xs text-blue-600 dark:text-blue-400'>";
                                $content .= "<svg class='w-4 h-4 mr-1.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
                                $content .= "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>";
                                $content .= "</svg>";
                                $content .= "💡 Untuk mengedit reviewer notes, gunakan halaman Review Submission";
                                $content .= "</div>";
                                $content .= "</div>";
                                $content .= "</div>";
                                
                                return new \Illuminate\Support\HtmlString($content);
                            })
                            ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'admin']) && !empty($this->record->reviewer_notes)),
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

            $updatedRecord = $submissionService->updateSubmission(
                $record,
                $data,
                documents: $documents
            );

            if (isset($data['status']) && $data['status'] === 'submitted' && $record->status === 'draft') {
                Notification::make()
                    ->title('Submission successfully sent for review')
                    ->body('Your submission has been received and is now in the review process')
                    ->success()
                    ->send();
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
}
