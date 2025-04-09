<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubmissionTypes extends ListRecords
{
    protected static string $resource = SubmissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->disabled(!SubmissionTypeResource::canCreate()) // Make create button inactive
                ->tooltip(SubmissionTypeResource::canCreate() 
                    ? 'Create a new submission type' 
                    : 'Creating new submission types is currently disabled'),
        ];
    }
}
