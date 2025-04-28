<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWorkflowStage extends EditRecord
{
    protected static string $resource = WorkflowStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('manage_requirements')
                ->label('Manage Requirements')
                ->icon('heroicon-o-document-check')
                ->url(fn (): string => $this->getResource()::getUrl('manage-requirements', ['record' => $this->record])),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterSave(): void
    {
        Notification::make()
            ->title('Workflow stage updated successfully')
            ->success()
            ->send();
    }
}