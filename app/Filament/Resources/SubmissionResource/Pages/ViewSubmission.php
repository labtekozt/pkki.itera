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
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        $workflowService = app(WorkflowService::class);
        $availableActions = $workflowService->getAvailableActions($this->record);

        $actions = [
            Actions\EditAction::make(),
        ];

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
        return parent::infolist($infolist)
            ->schema([
                // Use the basic schema from the Resource
                ...SubmissionResource::getInfolistSchema($this->record),
                
                // Add reviewer notes section if available and submission needs revision or was rejected
                Section::make('Reviewer Notes')
                    ->description('Notes from reviewers regarding required revisions')
                    ->schema([
                        TextEntry::make('reviewer_notes')
                            ->label('Revision Notes')
                            ->html()
                            ->formatStateUsing(fn ($state) => nl2br(e($state)))
                            ->placeholder('No revision notes provided'),
                    ])
                    ->columns(1)
                    ->visible(fn () => 
                        !empty($this->record->reviewer_notes) && 
                        in_array($this->record->status, ['revision_needed', 'rejected'])
                    ),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SubmissionProgressWidget::make([
                'submission' => $this->record,
            ]),
        ];
    }
}
