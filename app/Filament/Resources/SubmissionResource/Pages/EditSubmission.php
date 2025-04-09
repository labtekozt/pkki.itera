<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Forms\SubmissionFormFactory;
use App\Filament\Resources\SubmissionResource;
use App\Models\SubmissionType;
use App\Services\SubmissionService;
use Filament\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\HasWizard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditSubmission extends EditRecord
{
    use HasWizard;
    
    protected static string $resource = SubmissionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('process')
                ->label('Process Submission')
                ->color('warning')
                ->icon('heroicon-o-cog')
                ->url(fn() => $this->getResource()::getUrl('process', ['record' => $this->record]))
                ->visible(fn() => 
                    $this->record->status !== 'draft' && 
                    $this->record->status !== 'completed' && 
                    auth()->user()->can('review_submissions')
                ),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    protected function getSteps(): array
    {
        return [
            Step::make('Basic Information')
                ->description('Edit submission basic details')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn() => $this->record->user_id),

                    Select::make('submission_type_id')
                        ->relationship('submissionType', 'name')
                        ->required()
                        ->disabled() // Cannot change submission type after creation
                        ->helperText('Submission type cannot be changed after creation')
                        ->default(fn() => $this->record->submission_type_id)
                        ->dehydrated(false),

                    TextInput::make('title')
                        ->required()
                        ->helperText('Clear and concise title describing the submission')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->maxLength(1000)
                        ->helperText('Brief overview of your submission that summarizes its key aspects')
                        ->columnSpanFull()
                        ->placeholder('Brief description about this submission'),
                        
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'in_review' => 'In Review',
                            'revision_needed' => 'Revision Needed',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default(fn() => $this->record->status)
                        ->required()
                        ->reactive()
                        ->visible(fn() => Auth::user()->can('review_submissions')),
                        
                    Select::make('current_stage_id')
                        ->relationship('currentStage', 'name')
                        ->options(function() {
                            $submissionType = $this->record->submissionType;
                            if (!$submissionType) return [];
                            
                            return $submissionType->workflowStages()
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->pluck('name', 'id');
                        })
                        ->default(fn() => $this->record->current_stage_id)
                        ->visible(function() {
                            return $this->record->status !== 'draft' && 
                                Auth::user()->can('review_submissions');
                        }),
                ]),
                
            Step::make('Type Details')
                ->description('Edit type-specific information')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema(function () {
                    $submissionType = $this->record->submissionType;
                    
                    if (!$submissionType) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('no_type')
                                ->content('No submission type associated with this record')
                                ->columnSpanFull(),
                        ];
                    }
                    
                    return SubmissionFormFactory::getFormForSubmissionType($submissionType->slug);
                }),
                
            Step::make('Comments & Tracking')
                ->description('Add comments and track changes')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    Textarea::make('comment')
                        ->label('Change Comments')
                        ->helperText('Explain the changes you are making to this submission')
                        ->placeholder('Describe the changes or updates you have made')
                        ->columnSpanFull(),
                        
                    \Filament\Forms\Components\Section::make('Tracking History')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('tracking_history')
                                ->content(function() {
                                    $history = $this->record->trackingHistory()
                                        ->with(['stage', 'processor'])
                                        ->orderBy('created_at', 'desc')
                                        ->take(5)
                                        ->get();
                                        
                                    if ($history->isEmpty()) {
                                        return 'No tracking history available';
                                    }
                                    
                                    $content = "### Recent Changes\n\n";
                                    foreach ($history as $entry) {
                                        $content .= "**" . $entry->created_at->format('Y-m-d H:i') . "** - ";
                                        $content .= "**" . ucfirst($entry->action) . "**: ";
                                        $content .= $entry->comment ?? 'No comment';
                                        $content .= " by " . ($entry->processor->fullname ?? 'System');
                                        $content .= "\n\n";
                                    }
                                    
                                    return new \Illuminate\Support\HtmlString(
                                        \Illuminate\Support\Str::markdown($content)
                                    );
                                })
                                ->columnSpanFull(),
                        ]),
                ])
                ->visible(function() {
                    // Only show this step if the submission is not a draft
                    return $this->record->status !== 'draft' || 
                           Auth::user()->can('review_submissions');
                }),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Make sure we keep track of who made the change
        $data['processed_by'] = Auth::id();
        
        // If no comment provided but status changed, add a default comment
        if (empty($data['comment']) && isset($data['status']) && $data['status'] !== $this->record->status) {
            $data['comment'] = "Status changed from {$this->record->status} to {$data['status']}";
        }
        
        // Don't save the comment to the submission itself
        unset($data['comment']);
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Use the SubmissionService to update the submission
        $submissionService = app(SubmissionService::class);
        
        try {
            $updatedRecord = $submissionService->updateSubmission(
                $record,
                $data,
                $data['documents'] ?? []
            );
            
            Notification::make()
                ->title('Submission updated successfully')
                ->success()
                ->send();
            
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
