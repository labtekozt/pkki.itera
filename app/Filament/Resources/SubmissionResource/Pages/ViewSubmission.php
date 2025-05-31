<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Filament\Widgets\SubmissionDiagram;
use App\Filament\Widgets\SubmissionProgressWidget;
use App\Services\WorkflowService;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        $workflowService = app(WorkflowService::class);
        $availableActions = $workflowService->getAvailableActions($this->record);

        $actions = [];

        // Add simplified edit option for elderly users
        if (in_array($this->record->status, ['draft', 'revision_needed'])) {
            $actions[] = Actions\Action::make('edit_simple')
                ->label('📝 Edit Mudah')
                ->icon('heroicon-o-heart')
                ->color('success')
                ->tooltip('Interface yang lebih sederhana dan mudah digunakan')
                ->url(fn () => $this->getResource()::getUrl('edit-simple', ['record' => $this->record]));
        }

        // Customize the edit button text based on submission status
        if ($this->record->status === 'draft') {
            $actions[] = Actions\EditAction::make()
                ->label('Edit Advanced')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->tooltip('Interface lengkap dengan semua fitur');
        } elseif ($this->record->status === 'revision_needed') {
            $actions[] = Actions\EditAction::make()
                ->label('Update Submission')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->tooltip('Interface lengkap dengan semua fitur');
        } else {
            $actions[] = Actions\EditAction::make();
        }

        // Add workflow actions if available
        if (!empty($availableActions) && auth()->user()->can('review_submissions')) {
            $actions[] = Actions\Action::make('process')
                ->label('Process')
                ->color('warning')
                ->icon('heroicon-o-cog')
                ->url(fn() => $this->getResource()::getUrl('process', ['record' => $this->record]))
                ->visible(
                    fn() =>
                    $this->record->status !== 'draft' &&
                        $this->record->status !== 'completed'
                );
        }

        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the basic schema from the Resource
        $baseSchema = SubmissionResource::getInfolistSchema($this->record);
        
        // Create our custom schema components
        $interfaceSelectionSection = $this->getInterfaceSelectionSection();
        $statusGuidanceSection = $this->getStatusGuidanceSection();
        $documentStatusSection = $this->getDocumentStatusSection();
        
        // Build the schema array, ensuring no null values
        $schema = [];
        
        // Add interface selection guide if submission can be edited
        if ($interfaceSelectionSection !== null) {
            $schema[] = $interfaceSelectionSection;
        }
        
        // Add status guidance section if not null
        if ($statusGuidanceSection !== null) {
            $schema[] = $statusGuidanceSection;
        }
        
        // Add document status section if not null
        if ($documentStatusSection !== null) {
            $schema[] = $documentStatusSection;
        }
        
        // Add the base schema components
        $schema = array_merge($schema, $baseSchema);
        
        // Add document feedback section if there are reviewer notes
        $documentFeedbackSection = $this->getDocumentFeedbackSection();
        if ($documentFeedbackSection !== null) {
            $schema[] = $documentFeedbackSection;
        }
        
        // Add reviewer notes section if applicable
        if (!empty($this->record->reviewer_notes) && 
            in_array($this->record->status, ['revision_needed', 'rejected'])
        ) {
            $schema[] = Section::make('Reviewer Notes')
                ->description('Notes from reviewers regarding required revisions')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    TextEntry::make('reviewer_notes')
                        ->label('Revision Notes')
                        ->html()
                        ->formatStateUsing(fn ($state) => nl2br(e($state)))
                        ->placeholder('No revision notes provided'),
                ])
                ->columns(1);
        }
        
        return parent::infolist($infolist)
            ->schema($schema);
    }

    /**
     * Get status guidance section based on current submission status
     */
    protected function getStatusGuidanceSection()
    {
        return Section::make('Submission Status')
            ->description($this->getStatusDescription($this->record->status))
            ->icon($this->getStatusIcon($this->record->status))
            ->schema([
                TextEntry::make('status_guidance')
                    ->html()
                    ->formatStateUsing(function () {
                        return view('filament.components.submission-status-guidance', [
                            'submission' => $this->record,
                            'status' => $this->record->status,
                            'nextSteps' => $this->getNextSteps($this->record->status),
                            'documentComplete' => $this->isDocumentationComplete(),
                        ])->render();
                    })
                    ->hiddenLabel()
            ])
            ->extraAttributes([
                'class' => match ($this->record->status) {
                    'draft' => 'border-l-4 border-gray-500',
                    'submitted' => 'border-l-4 border-blue-500',
                    'in_review' => 'border-l-4 border-amber-500',
                    'revision_needed' => 'border-l-4 border-red-500',
                    'approved' => 'border-l-4 border-emerald-500',
                    'rejected' => 'border-l-4 border-red-700',
                    'completed' => 'border-l-4 border-green-700',
                    'cancelled' => 'border-l-4 border-gray-700',
                    default => '',
                }
            ]);
    }

    /**
     * Get document status section
     */
    protected function getDocumentStatusSection()
    {
        // Only show document status section if submission is in draft
        // or needs revision
        if (!in_array($this->record->status, ['draft', 'revision_needed'])) {
            return null;
        }
        
        $documentComplete = $this->isDocumentationComplete();
        $icon = $documentComplete ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle';
        $iconColor = $documentComplete ? 'success' : 'danger';
        $title = $documentComplete ? 'All Required Documents Uploaded' : 'Missing Required Documents';
        
        return Section::make($title)
            ->icon($icon)
            ->iconColor($iconColor)
            ->schema([
                TextEntry::make('document_status')
                    ->html()
                    ->formatStateUsing(function () use ($documentComplete) {
                        return view('filament.components.document-status', [
                            'submission' => $this->record,
                            'documentComplete' => $documentComplete,
                            'missingDocuments' => $this->getMissingDocuments(),
                        ])->render();
                    })
                    ->hiddenLabel()
            ]);
    }

    /**
     * Get missing documents list
     */
    protected function getMissingDocuments(): array
    {
        if (!$this->record || !$this->record->submissionType) {
            return [];
        }

        $requirements = $this->record->submissionType->documentRequirements()
            ->where('required', true)
            ->get();
            
        if ($requirements->isEmpty()) {
            return [];
        }
        
        $missingDocuments = [];
        
        foreach ($requirements as $requirement) {
            $document = $this->record->submissionDocuments()
                ->where('requirement_id', $requirement->id)
                ->where('status', '!=', 'replaced')
                ->latest()
                ->first();
                
            if (!$document) {
                $missingDocuments[] = [
                    'name' => $requirement->name,
                    'description' => $requirement->description
                ];
            }
        }
        
        return $missingDocuments;
    }
    
    /**
     * Check if all required documents have been uploaded
     */
    protected function isDocumentationComplete(): bool
    {
        return empty($this->getMissingDocuments());
    }

    /**
     * Get appropriate icon for current status
     */
    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'draft' => 'heroicon-o-pencil',
            'submitted' => 'heroicon-o-paper-airplane',
            'in_review' => 'heroicon-o-document-magnifying-glass',
            'revision_needed' => 'heroicon-o-document-minus',
            'approved' => 'heroicon-o-check-badge',
            'rejected' => 'heroicon-o-x-circle',
            'completed' => 'heroicon-o-trophy',
            'cancelled' => 'heroicon-o-x-mark',
            default => 'heroicon-o-question-mark-circle',
        };
    }
    
    /**
     * Get status description
     */
    protected function getStatusDescription(string $status): string
    {
        return match ($status) {
            'draft' => 'This submission is still in draft mode and can be edited',
            'submitted' => 'Your submission has been sent for review',
            'in_review' => 'Your submission is currently being reviewed',
            'revision_needed' => 'Your submission needs updates before it can be approved',
            'approved' => 'Your submission has been approved',
            'rejected' => 'Your submission was not approved',
            'completed' => 'Your submission has been completed and certified',
            'cancelled' => 'This submission has been cancelled',
            default => 'Current submission status',
        };
    }
    
    /**
     * Get next steps based on status
     */
    protected function getNextSteps(string $status): array
    {
        return match ($status) {
            'draft' => [
                [
                    'step' => 'Complete all required information',
                    'description' => 'Make sure all fields are filled in correctly',
                    'done' => true,
                ],
                [
                    'step' => 'Upload all required documents',
                    'description' => 'All required documents must be uploaded',
                    'done' => $this->isDocumentationComplete(),
                ],
                [
                    'step' => 'Submit for review',
                    'description' => 'Once everything is complete, submit your application',
                    'done' => false,
                ],
            ],
            'submitted' => [
                [
                    'step' => 'Wait for review',
                    'description' => 'Your submission is waiting to be reviewed',
                    'done' => false,
                ],
            ],
            'in_review' => [
                [
                    'step' => 'Wait for review completion',
                    'description' => 'Your submission is being reviewed',
                    'done' => false,
                ],
            ],
            'revision_needed' => [
                [
                    'step' => 'Read reviewer notes',
                    'description' => 'Check what needs to be fixed',
                    'done' => true,
                ],
                [
                    'step' => 'Make required changes',
                    'description' => 'Update your submission based on reviewer feedback',
                    'done' => false,
                ],
                [
                    'step' => 'Resubmit your application',
                    'description' => 'After making changes, submit again for review',
                    'done' => false,
                ],
            ],
            default => [],
        };
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SubmissionProgressWidget::make([
                'submission' => $this->record,
            ]),
        ];
    }

    /**
     * Get interface selection section to guide users between simple and advanced editing
     */
    protected function getInterfaceSelectionSection()
    {
        // Only show interface selection if submission can be edited
        if (!in_array($this->record->status, ['draft', 'revision_needed'])) {
            return null;
        }

        return Section::make('📝 Pilih Cara Edit')
            ->description('Pilih interface yang paling sesuai dengan kebutuhan Anda')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                TextEntry::make('interface_selection')
                    ->html()
                    ->formatStateUsing(function () {
                        return view('filament.components.interface-selection-guide', [
                            'submission' => $this->record,
                            'simpleEditUrl' => $this->getResource()::getUrl('edit-simple', ['record' => $this->record]),
                            'advancedEditUrl' => $this->getResource()::getUrl('edit', ['record' => $this->record]),
                        ])->render();
                    })
                    ->hiddenLabel()
            ])
            ->extraAttributes([
                'class' => 'border-l-4 border-green-500 bg-green-50',
            ])
            ->collapsible()
            ->collapsed(false);
    }

    /**
     * Get document feedback section to display reviewer notes prominently
     */
    protected function getDocumentFeedbackSection()
    {
        $documentsWithFeedback = $this->record->submissionDocuments()
            ->whereNotNull('notes')
            ->whereIn('status', ['approved', 'rejected', 'revision_needed'])
            ->with(['document', 'requirement'])
            ->latest()
            ->get();

        if ($documentsWithFeedback->isEmpty()) {
            return null;
        }

        return Section::make('Document Review Feedback')
            ->description('Feedback from reviewers on your submitted documents')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                TextEntry::make('document_feedback')
                    ->html()
                    ->formatStateUsing(function () use ($documentsWithFeedback) {
                        return view('filament.components.document-feedback-list', [
                            'documentsWithFeedback' => $documentsWithFeedback,
                        ])->render();
                    })
                    ->hiddenLabel()
            ])
            ->extraAttributes([
                'class' => 'border-l-4 border-blue-500',
            ]);
    }
}
