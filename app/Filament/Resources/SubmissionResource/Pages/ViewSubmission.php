<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
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
        return [
            Actions\EditAction::make(),
            Actions\Action::make('process')
                ->label('Process')
                ->color('warning')
                ->icon('heroicon-o-cog')
                ->url(fn() => $this->getResource()::getUrl('process', ['record' => $this->record]))
                ->visible(fn() => 
                    $this->record->status !== 'draft' && 
                    $this->record->status !== 'completed' && 
                    auth()->user()->can('review_submissions')
                ),
            Actions\Action::make('documents')
                ->label('Manage Documents')
                ->icon('heroicon-o-document-duplicate')
                ->url(fn() => $this->getResource()::getUrl('documents', ['record' => $this->record])),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return parent::infolist($infolist)
            ->schema([
                // Use the basic schema from the Resource
                ...SubmissionResource::getInfolistSchema($this->record),
                
                // Add tracking history section
                Section::make('Tracking History')
                    ->schema([
                        RepeatableEntry::make('trackingHistory')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Date & Time')
                                    ->dateTime(),
                                
                                TextEntry::make('action')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                    ->color(fn($record) => match($record->action) {
                                        'create' => 'info',
                                        'update' => 'warning',
                                        'advance' => 'success', 
                                        'revert' => 'danger',
                                        'document_update' => 'primary',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn($record) => match($record->status) {
                                        'started' => 'gray',
                                        'in_progress' => 'info',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'revision_needed' => 'warning',
                                        'objection' => 'danger',
                                        'completed' => 'success',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('stage.name')
                                    ->label('Stage'),
                                
                                TextEntry::make('comment')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn($state) => $state ?? 'No comment provided'),
                                
                                TextEntry::make('processor.fullname')
                                    ->label('Processed By')
                                    ->formatStateUsing(fn($state) => $state ?? 'System'),
                            ])
                            ->columns(3)
                    ])
                    ->collapsible(),
                
                // Add document section
                Section::make('Documents')
                    ->schema([
                        RepeatableEntry::make('submissionDocuments')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('document.title')
                                    ->label('Document'),
                                
                                TextEntry::make('requirement.name')
                                    ->label('Requirement'),
                                
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn($record) => match($record->status) {
                                        'pending' => 'gray',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'revision_needed' => 'warning',
                                        'replaced' => 'info',
                                        'final' => 'success',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('notes')
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn($state) => $state ?? 'No notes provided'),
                                
                                Action::make('view_document')
                                    ->label('View')
                                    ->url(fn($record) => route('documents.view', $record->document_id))
                                    ->icon('heroicon-o-document-magnifying-glass')
                                    ->color('primary')
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(5)
                    ])
                    ->collapsible(),
            ]);
    }
}
