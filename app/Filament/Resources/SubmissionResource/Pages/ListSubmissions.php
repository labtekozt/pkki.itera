<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListSubmissions extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SubmissionsOverview::class,
        ];
    }
}
