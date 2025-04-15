<?php

namespace App\Filament\Resources\SubmissionResource\RelationManagers;

use App\Models\DocumentRequirement;
use App\Services\DocumentPermissionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
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
                    ->relationship('requirement', 'name', function (Builder $query) {
                        $submissionTypeId = $this->ownerRecord->submission_type_id;
                        if ($submissionTypeId) {
                            $query->where('submission_type_id', $submissionTypeId);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(!$this->permissionService->canEditDocuments()),

                // Hidden field to track the original document URI
                Forms\Components\Hidden::make('original_document_uri'),

                Forms\Components\Select::make('status')
                    ->options([
                        self::STATUS_PENDING => 'Pending',
                        self::STATUS_APPROVED => 'Approved',
                        self::STATUS_REJECTED => 'Rejected',
                        self::STATUS_REVISION_NEEDED => 'Revision Needed',
                        self::STATUS_REPLACED => 'Replaced',
                        self::STATUS_FINAL => 'Final',
                    ])
                    ->default(self::STATUS_PENDING)
                    ->required()
                    ->disabled(!$this->permissionService->canReviewDocuments()),

                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        // Common columns that both reviewers and users can see
        $columns = [
            Tables\Columns\TextColumn::make('requirement.name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('document.title')
                ->searchable()
                ->url(fn($record) => route('filament.admin.documents.download', $record->document_id))
                ->openUrlInNewTab()
                ->icon('heroicon-m-document')
                ->iconPosition(IconPosition::After)
                ->tooltip('Click to download'),



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
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(),
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
                ->action(function (array $data, $record): void {
                    $record->update([
                        'status' => $data['status'],
                        'notes' => $data['review_notes'],
                    ]);

                    // In a real application, you might want to notify the user here
                    // or create a tracking history entry
                })
                ->modalHeading('Review Document')
                ->modalSubmitActionLabel('Submit Review');
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
            ->bulkActions($bulkActions);
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
