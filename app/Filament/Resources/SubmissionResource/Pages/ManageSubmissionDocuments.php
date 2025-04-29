<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\Document;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageSubmissionDocuments extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = SubmissionResource::class;
    
    protected static string $view = 'filament.resources.submission-resource.pages.manage-submission-documents';
    
    public ?Submission $record = null;
    
    public function mount(string $record): void
    {
        $this->record = Submission::findOrFail($record);
    }
    
    /**
     * Define the base query for the table records.
     */
    protected function getTableQuery(): Builder
    {
        return SubmissionDocument::query()
            ->where('submission_id', $this->record->id)
            ->with(['document', 'requirement']);
    }
    
    /**
     * Define the form schema for the upload form.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('requirement_id')
                    ->label('Document Requirement')
                    ->options(function () {
                        // Get requirements for this submission type
                        return $this->record->submissionType
                            ->documentRequirements()
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
                    
                FileUpload::make('document_file')
                    ->label('Document File')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->directory('submissions/' . $this->record->id)
                    ->preserveFilenames()
                    ->maxSize(10240) // 10MB max upload
                    ->required(),
                    
                Textarea::make('notes')
                    ->label('Notes')
                    ->placeholder('Optional notes about this document')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Define the table for displaying documents.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requirement.name')
                    ->label('Requirement')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('document.title')
                    ->label('Document Title')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('document.mimetype')
                    ->label('File Type'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revision_needed' => 'warning',
                        'replaced' => 'info',
                        'final' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revision_needed' => 'Revision Needed',
                        'replaced' => 'Replaced',
                        'final' => 'Final',
                    ]),
                
                Tables\Filters\SelectFilter::make('requirement_id')
                    ->label('Requirement')
                    ->options(function () {
                        return $this->record->submissionType
                            ->documentRequirements()
                            ->pluck('name', 'id');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (SubmissionDocument $record): string => 
                        Storage::disk('public')->url($record->document->uri)),
                    
                Tables\Actions\EditAction::make()
                    ->form([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'revision_needed' => 'Revision Needed',
                            ])
                            ->required(),
                            
                        Textarea::make('notes')
                            ->placeholder('Notes about this document')
                            ->columnSpanFull(),
                    ]),
                    
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'approved']);
                            }
                        })
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Submission')
                ->url(fn () => SubmissionResource::getUrl('view', ['record' => $this->record->id]))
                ->color('secondary'),
                
            Actions\Action::make('upload')
                ->label('Upload Document')
                ->form(function (Form $form) {
                    return $this->form($form);
                })
                ->action(function (array $data) {
                    // Create Document record
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['document_file'];
                    
                    $document = Document::create([
                        'title' => $file->getClientOriginalName(),
                        'uri' => $file->store('submissions/' . $this->record->id, 'public'),
                        'mimetype' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                    
                    // Create SubmissionDocument record
                    SubmissionDocument::create([
                        'submission_id' => $this->record->id,
                        'document_id' => $document->id,
                        'requirement_id' => $data['requirement_id'],
                        'status' => 'pending',
                        'notes' => $data['notes'] ?? null,
                    ]);
                })
                ->modalHeading('Upload Document')
        ];
    }
    
    /**
     * Update document status with confirmation
     */
    public function updateDocumentStatus(string $documentId, string $status, ?string $notes = null): void
    {
        try {
            // Find the submission document
            $document = $this->record->submissionDocuments()->find($documentId);
            
            if (!$document) {
                Notification::make()
                    ->title('Document not found')
                    ->danger()
                    ->send();
                return;
            }

            // Update the document status in the database
            $document->update([
                'status' => $status,
                'notes' => $notes,
            ]);

            // Show success notification
            $statusLabel = ucfirst(str_replace('_', ' ', $status));
            Notification::make()
                ->title("Document marked as {$statusLabel}")
                ->success()
                ->send();
                
            // Refresh table to show updated data
            $this->refreshTable();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating document status')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
