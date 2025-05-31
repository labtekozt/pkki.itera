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
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use App\Models\Submission;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        $workflowService = app(WorkflowService::class);
        $availableActions = $workflowService->getAvailableActions($this->record);

        $actions = [];

        // Customize the edit button text based on submission status
        if ($this->record->status === 'draft') {
            $actions[] = Actions\EditAction::make()
                ->label(__('resource.submission.actions.edit_draft'))
                ->icon('heroicon-o-pencil-square')
                ->color('primary');
        } elseif ($this->record->status === 'revision_needed') {
            $actions[] = Actions\EditAction::make()
                ->label(__('resource.submission.actions.update_submission'))
                ->icon('heroicon-o-pencil-square')
                ->color('warning');
        } else {
            $actions[] = Actions\EditAction::make()
                ->label(__('actions.edit'));
        }

        // Add workflow actions if available
        if (!empty($availableActions) && auth()->user()->can('review_submissions')) {
            $actions[] = Actions\Action::make('process')
                ->label(__('resource.submission.actions.process'))
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
        $statusGuidanceSection = $this->getStatusGuidanceSection();
        $documentStatusSection = $this->getDocumentStatusSection();
        
        // Build the schema array, ensuring no null values
        $schema = [];
        
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
        
        // Add certificate section for completed submissions
        $certificateSection = $this->getCertificateSection();
        if ($certificateSection !== null) {
            $schema[] = $certificateSection;
        }
        
        // Add reviewer notes section if applicable
        if (!empty($this->record->reviewer_notes) && 
            in_array($this->record->status, ['revision_needed', 'rejected'])
        ) {
            $schema[] = Section::make(__('resource.submissions.reviewer_notes'))
                ->description(__('resource.submissions.reviewer_notes_description'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    TextEntry::make('reviewer_notes')
                        ->label(__('resource.submissions.revision_notes'))
                        ->html()
                        ->formatStateUsing(fn ($state) => nl2br(e($state)))
                        ->placeholder(__('resource.submissions.no_revision_notes')),
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
        return Section::make(__('resource.submissions.submission_status'))
            ->description($this->getStatusDescription($this->record->status))
            ->icon($this->getStatusIcon($this->record->status))
            ->schema([
                ViewEntry::make('status_guidance')
                    ->view('filament.components.submission-status-guidance', [
                        'submission' => $this->record,
                        'status' => $this->record->status,
                        'nextSteps' => $this->getNextSteps($this->record->status),
                        'documentComplete' => $this->isDocumentationComplete(),
                    ])
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
                ViewEntry::make('document_status')
                    ->view('filament.components.document-status', [
                        'submission' => $this->record,
                        'documentComplete' => $documentComplete,
                        'missingDocuments' => $this->getMissingDocuments(),
                    ])
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
     * Get certificate section for completed submissions
     */
    protected function getCertificateSection(): ?Section
    {
        // Only show certificate section for completed submissions
        if ($this->record->status !== 'completed') {
            return null;
        }

        return Section::make('🎉 Sertifikat Tersedia')
            ->description('Sertifikat kekayaan intelektual Anda telah selesai diproses')
            ->icon('heroicon-o-trophy')
            ->iconColor('success')
            ->schema([
                ViewEntry::make('certificate_status')
                    ->view('filament.components.certificate-status', [
                        'submission' => $this->record,
                        'certificateMetadata' => $this->getCertificateMetadata($this->record),
                        'downloadAction' => $this->downloadCertificateAction(),
                    ])
            ])
            ->extraAttributes([
                'class' => 'border-l-4 border-green-500 bg-gradient-to-r from-green-50 to-emerald-50'
            ]);
    }

    /**
     * Get certificate metadata from tracking history
     */
    private function getCertificateMetadata(Submission $submission): ?array
    {
        // Get the latest tracking history entry for certificate upload
        $certificateHistory = $submission->trackingHistory()
            ->where('action', 'certificate_uploaded')
            ->latest()
            ->first();
        
        if (!$certificateHistory || !$certificateHistory->metadata) {
            return null;
        }
        
        return $certificateHistory->metadata;
    }

    /**
     * Create download certificate action
     */
    private function downloadCertificateAction(): Action
    {
        return Action::make('downloadCertificate')
            ->label('📥 Unduh Sertifikat')
            ->size('xl')
            ->color('success')
            ->icon('heroicon-o-document-arrow-down')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Unduh Sertifikat')
            ->modalDescription('Apakah Anda yakin ingin mengunduh sertifikat?')
            ->modalSubmitActionLabel('Ya, Unduh')
            ->modalCancelActionLabel('Batal')
            ->action(function () {
                return $this->downloadCertificate();
            });
    }

    /**
     * Handle certificate download
     */
    protected function downloadCertificate()
    {
        $submission = $this->record;
        
        // Check if user can access this certificate
        if (!auth()->user()->can('view', $submission)) {
            Notification::make()
                ->title('❌ Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengunduh sertifikat ini.')
                ->danger()
                ->send();
            return;
        }

        // Check if certificate file exists
        if (!$submission->certificate || !Storage::exists($submission->certificate)) {
            Notification::make()
                ->title('⚠️ File Tidak Ditemukan')
                ->body('Maaf, file sertifikat tidak tersedia. Silakan hubungi admin untuk bantuan.')
                ->warning()
                ->send();
            return;
        }

        try {
            // Generate descriptive filename
            $filename = $this->generateCertificateFilename($submission);
            
            // Show success notification
            Notification::make()
                ->title('✅ Unduhan Dimulai')
                ->body("Sertifikat \"$filename\" sedang diunduh. Periksa folder Downloads Anda.")
                ->success()
                ->duration(5000)
                ->send();

            // Return download response
            return Storage::download($submission->certificate, $filename);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Gagal Mengunduh')
                ->body('Terjadi kesalahan saat mengunduh sertifikat. Silakan coba lagi atau hubungi admin.')
                ->danger()
                ->send();
        }
    }

    /**
     * Generate human-readable filename for certificate download
     */
    private function generateCertificateFilename(Submission $submission): string
    {
        $metadata = $this->getCertificateMetadata($submission);
        $certificateNumber = $metadata['certificate_number'] ?? 'CERT-' . $submission->id;
        
        // Clean title for filename
        $cleanTitle = preg_replace('/[^a-zA-Z0-9\s]/', '', $submission->title);
        $cleanTitle = preg_replace('/\s+/', '_', trim($cleanTitle));
        $cleanTitle = substr($cleanTitle, 0, 50); // Limit length
        
        return "Sertifikat_{$certificateNumber}_{$cleanTitle}.pdf";
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
