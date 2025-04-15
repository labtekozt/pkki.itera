<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListWorkflowStages extends ListRecords
{
    protected static string $resource = WorkflowStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}