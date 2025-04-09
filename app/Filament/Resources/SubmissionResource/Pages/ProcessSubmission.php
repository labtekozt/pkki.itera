<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\Document;
use App\Models\SubmissionDocument;
use App\Repositories\SubmissionRepository;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProcessSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected static ?string $title = 'Process Submission';

    public function getSubheading(): string
    {
        return "Current Stage: {$this->record->currentStage->name} | Status: {$this->record->status}";
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Check if current user can process this submission
        if (Auth::user()->can('review_submissions')) {
            // Only show advance action if submission is in review and has a current stage
            if ($this->record->status === 'in_review' && $this->record->currentStage) {
                $actions[] = Actions\Action::make('advanceStage')
                    ->label('Advance to Next Stage')
                    ->color('success')
                    ->icon('heroicon-o-arrow-right')
                    ->requiresConfirmation()
                    ->modalHeading('Advance Submission')
                    ->modalDescription('Are you sure you want to advance this submission to the next stage?')
                    ->modalSubmitActionLabel('Yes, Advance')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Comments')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $submissionRepo = app(SubmissionRepository::class);

                        try {
                            $submissionRepo->advanceSubmission($this->record, [
                                'comment' => $data['comment'],
                                'processed_by' => Auth::id(),
                            ]);

                            Notification::make()
                                ->title('Submission advanced successfully')
                                ->success()
                                ->send();

                            $this->redirect(SubmissionResource::getUrl('view', ['record' => $this->record]));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error advancing submission')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    });
            }

            // Document review actions
            $actions[] = Actions\Action::make('reviewDocuments')
                ->label('Review Documents')
                ->color('warning')
                ->icon('heroicon-o-document-magnifying-glass')
                ->url(fn() => SubmissionResource::getUrl('documents', ['record' => $this->record]));
        }

        return $actions;
    }
}
