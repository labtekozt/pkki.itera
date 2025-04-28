<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CreateWorkflowStage extends CreateRecord
{
    protected static string $resource = WorkflowStageResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id'] = Str::uuid()->toString();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Workflow stage created successfully')
            ->body('The stage has been added to the workflow')
            ->success()
            ->send();
    }
}