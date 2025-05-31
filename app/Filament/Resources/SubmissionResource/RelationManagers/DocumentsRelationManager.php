<?php

namespace App\Filament\Resources\SubmissionResource\RelationManagers;

use App\Models\DocumentRequirement;
use App\Services\DocumentPermissionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\Document;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissionDocuments';

    // Define document statuses as constants for better maintainability
    private const STATUS_PENDING = 'pending';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_REVISION_NEEDED = 'revision_needed';
    private const STATUS_REPLACED = 'replaced';
    private const STATUS_FINAL = 'final';

    // Permission service dependency to follow SOLID principles
    protected ?DocumentPermissionService $permissionService = null;

    // Fixed boot method to properly resolve the service
    public function boot(): void
    {

        // Manually instantiate the service to avoid container resolution issues
        $this->permissionService = new DocumentPermissionService();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('requirement_id')
                    ->label('Document Type')
                    ->relationship('requirement', 'name', function (Builder $query) {
                        $submissionTypeId = $this->ownerRecord->submission_type_id;
                        if ($submissionTypeId) {
                            $query->where('submission_type_id', $submissionTypeId);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(!$this->permissionService->canEditDocuments())
                    ->helperText('Select the type of document you are uploading'),

                // Hidden field to track the original document URI
                Forms\Components\Hidden::make('original_document_uri'),

                Forms\Components\Select::make('status')
                    ->label('Review Status')
                    ->options([
                        self::STATUS_PENDING => 'Pending Review',
                        self::STATUS_APPROVED => 'Approved',
                        self::STATUS_REJECTED => 'Rejected',
                        self::STATUS_REVISION_NEEDED => 'Revision Needed',
                        self::STATUS_REPLACED => 'Replaced',
                        self::STATUS_FINAL => 'Final',
                    ])
                    ->default(self::STATUS_PENDING)
                    ->required()
                    ->disabled(!$this->permissionService->canReviewDocuments())
                    ->visible($this->permissionService->canReviewDocuments())
                    ->helperText('Set the review status for this document'),

                Forms\Components\Textarea::make('notes')
                    ->label(fn(): string => $this->permissionService->canReviewDocuments() 
                        ? 'Reviewer Notes' 
                        : 'Additional Information')
                    ->placeholder(fn(): string => $this->permissionService->canReviewDocuments() 
                        ? 'Provide feedback about the document quality, required changes, or approval notes...' 
                        : 'Add any additional information about this document (optional)...')
                    ->maxLength(65535)
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText(fn(): string => $this->permissionService->canReviewDocuments() 
                        ? 'These notes will be visible to the document submitter to help them understand any required changes.'
                        : 'This information may help reviewers understand the context of your document.'),
            ]);
    }

    public function table(Table $table): Table
    {
        // Common columns that both reviewers and users can see
        $columns = [
            Tables\Columns\TextColumn::make('requirement.name')
                ->searchable()
                ->sortable()
                ->weight('medium'),

            Tables\Columns\TextColumn::make('document.title')
                ->searchable()
                ->url(fn($record) => route('filament.admin.documents.download', $record->document_id))
                ->openUrlInNewTab()
                ->icon('heroicon-m-document')
                ->iconPosition(IconPosition::After)
                ->tooltip('Click to download')
                ->weight('medium')
                ->wrap(),

            Tables\Columns\TextColumn::make('document.mimetype')
                ->label('File Type')
                ->badge()
                ->color('gray'),

            Tables\Columns\TextColumn::make('document.human_size')
                ->label('Size'),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    self::STATUS_PENDING => 'gray',
                    self::STATUS_APPROVED => 'success',
                    self::STATUS_REJECTED => 'danger',
                    self::STATUS_REVISION_NEEDED => 'warning',
                    self::STATUS_REPLACED => 'info',
                    self::STATUS_FINAL => 'success',
                    default => 'gray',
                })
                ->tooltip(fn($record): string => match ($record->status) {
                    self::STATUS_PENDING => 'Document is awaiting review',
                    self::STATUS_APPROVED => 'Document has been approved',
                    self::STATUS_REJECTED => 'Document was rejected and needs to be replaced',
                    self::STATUS_REVISION_NEEDED => 'Document needs revision based on reviewer feedback',
                    self::STATUS_REPLACED => 'Document has been replaced with a newer version',
                    self::STATUS_FINAL => 'Document is final and approved',
                    default => 'Unknown status',
                }),

            // Enhanced reviewer notes column with better visibility
            Tables\Columns\TextColumn::make('notes')
                ->label('Reviewer Feedback')
                ->wrap()
                ->limit(100)
                ->tooltip(fn($record): ?string => $record->notes)
                ->placeholder('No feedback yet')
                ->color(fn($record): string => match ($record->status) {
                    self::STATUS_REJECTED => 'danger',
                    self::STATUS_REVISION_NEEDED => 'warning',
                    self::STATUS_APPROVED => 'success',
                    default => 'gray',
                })
                ->icon(fn($record): ?string => match ($record->status) {
                    self::STATUS_REJECTED => 'heroicon-m-x-circle',
                    self::STATUS_REVISION_NEEDED => 'heroicon-m-exclamation-triangle',
                    self::STATUS_APPROVED => 'heroicon-m-check-circle',
                    default => null,
                })
                ->iconPosition(IconPosition::Before)
                ->weight(fn($record): string => $record->notes ? 'semibold' : 'normal')
                ->formatStateUsing(function ($state, $record) {
                    if (!$state) {
                        return match ($record->status) {
                            self::STATUS_PENDING => 'Awaiting review...',
                            self::STATUS_APPROVED => 'Approved ✓',
                            default => 'No feedback',
                        };
                    }
                    return $state;
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Uploaded')
                ->dateTime()
                ->sortable()
                ->toggleable()
                ->since()
                ->tooltip(fn($record): string => $record->created_at->format('M d, Y g:i A')),
        ];

        // Common filters for all users
        $filters = [
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    self::STATUS_PENDING => 'Pending',
                    self::STATUS_APPROVED => 'Approved',
                    self::STATUS_REJECTED => 'Rejected',
                    self::STATUS_REVISION_NEEDED => 'Revision Needed',
                    self::STATUS_REPLACED => 'Replaced',
                    self::STATUS_FINAL => 'Final',
                ]),

            Tables\Filters\SelectFilter::make('requirement_id')
                ->relationship('requirement', 'name')
                ->searchable()
                ->preload()
                ->label('Document Type'),
        ];

        // Common view actions for all users
        $actions = [
            // View feedback action - prominent for users to understand document status
            Tables\Actions\Action::make('viewFeedback')
                ->label('View Feedback')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(fn($record): string => match ($record->status) {
                    self::STATUS_REJECTED => 'danger',
                    self::STATUS_REVISION_NEEDED => 'warning',
                    self::STATUS_APPROVED => 'success',
                    default => 'gray',
                })
                ->visible(fn($record): bool => !empty($record->notes) || in_array($record->status, [
                    self::STATUS_APPROVED, 
                    self::STATUS_REJECTED, 
                    self::STATUS_REVISION_NEEDED
                ]))
                ->modalHeading(fn($record): string => "Reviewer Feedback: {$record->document->title}")
                ->modalDescription(fn($record): string => "Status: " . ucfirst(str_replace('_', ' ', $record->status)))
                ->modalContent(function ($record) {
                    $statusInfo = match ($record->status) {
                        self::STATUS_APPROVED => [
                            'icon' => 'heroicon-o-check-circle',
                            'color' => 'success',
                            'message' => 'This document has been approved and meets all requirements.'
                        ],
                        self::STATUS_REJECTED => [
                            'icon' => 'heroicon-o-x-circle', 
                            'color' => 'danger',
                            'message' => 'This document has been rejected and needs to be replaced with a corrected version.'
                        ],
                        self::STATUS_REVISION_NEEDED => [
                            'icon' => 'heroicon-o-exclamation-triangle',
                            'color' => 'warning', 
                            'message' => 'This document needs revision. Please review the feedback below and make necessary changes.'
                        ],
                        default => [
                            'icon' => 'heroicon-o-clock',
                            'color' => 'gray',
                            'message' => 'This document is pending review.'
                        ]
                    };

                    return view('filament.components.document-feedback', [
                        'record' => $record,
                        'statusInfo' => $statusInfo,
                        'notes' => $record->notes ?: 'No specific feedback provided.',
                        'reviewedAt' => $record->updated_at,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            // View/download file action
            Tables\Actions\Action::make('view')
                ->label('View File')
                ->icon('heroicon-o-eye')
                ->url(fn($record) => route('filament.admin.documents.view', $record->document_id))
                ->openUrlInNewTab()
                ->visible(fn($record) => $record->document && in_array($record->document->mimetype, [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/svg+xml',
                    'text/plain',
                    'text/html',
                ])),

            // Download action for all file types
            Tables\Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn($record) => route('filament.admin.documents.download', $record->document_id))
                ->color('gray'),
        ];

        // Add edit/delete actions only for admin and superadmin roles
        if ($this->permissionService->canEditDocuments()) {
            $actions[] = Tables\Actions\DeleteAction::make();
        }

        // Add review action only for those with review permissions
        if ($this->permissionService->canReviewDocuments()) {
            $actions[] = Tables\Actions\Action::make('review')
                ->label('Review Document')
                ->icon('heroicon-o-clipboard-document-check')
                ->form([
                    Forms\Components\Select::make('status')
                        ->options([
                            self::STATUS_APPROVED => 'Approve',
                            self::STATUS_REJECTED => 'Reject',
                            self::STATUS_REVISION_NEEDED => 'Request Revision',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('review_notes')
                        ->label('Review Notes')
                        ->required(),
                ])
                ->modalHeading('Document Review')
                ->modalDescription(fn($record) => "You are reviewing document: {$record->document->title}")
                ->modalSubmitActionLabel('Submit Review')
                ->action(function (array $data, $record): void {
                    $oldStatus = $record->status;
                    $record->update([
                        'status' => $data['status'],
                        'notes' => $data['review_notes'],
                    ]);

                    $statusLabel = match($data['status']) {
                        self::STATUS_APPROVED => 'approved',
                        self::STATUS_REJECTED => 'rejected',
                        self::STATUS_REVISION_NEEDED => 'marked for revision',
                        default => 'updated'
                    };

                    Notification::make()
                        ->title("Document {$statusLabel} successfully")
                        ->success()
                        ->send();
                });
        }

        // Add bulk actions for admin and superadmin roles only
        $bulkActions = [];
        if ($this->permissionService->canEditDocuments()) {
            $bulkActions = [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => self::STATUS_APPROVED]);
                            }
                        })
                        ->visible($this->permissionService->canReviewDocuments()),
                ]),
            ];
        }

        return $table
            ->recordTitleAttribute('document.title')
            ->columns($columns)
            ->filters($filters)
            ->actions($actions)
            ->bulkActions($bulkActions)
            ->headerActions([
                // Add info action to show document status summary
                Tables\Actions\Action::make('documentStatusInfo')
                    ->label('Document Status Guide')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->modalHeading('Document Status Guide')
                    ->modalDescription('Understanding document review statuses and what they mean')
                    ->modalContent(view('filament.components.document-status-guide'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->description('Upload and manage documents required for your submission. Click "View Feedback" to see reviewer comments and understand any required changes.')
            ->emptyStateHeading('No Documents Yet')
            ->emptyStateDescription('Upload documents required for your submission to get started. Each document will be reviewed and you\'ll receive feedback on any needed changes.')
            ->emptyStateIcon('heroicon-o-document-plus');
    }

    public function isListable(): bool
    {
        // Always allow viewing the tab, even for regular users
        return true;
    }

    public function canCreate(): bool
    {
        // Only allow creation for admin and reviewers
        return $this->permissionService->canEditDocuments();
    }

    public function canEdit(mixed $record): bool
    {
        // Only allow editing for admin and reviewers
        return $this->permissionService->canEditDocuments();
    }

    public function canDelete(mixed $record): bool
    {
        // Only allow deletion for admin and reviewers
        return $this->permissionService->canEditDocuments();
    }
}
