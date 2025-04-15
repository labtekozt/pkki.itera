<?php

namespace App\Filament\Resources\WorkflowAssignmentResource\Pages;

use App\Filament\Resources\WorkflowAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowAssignments extends ListRecords
{
    protected static string $resource = WorkflowAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}