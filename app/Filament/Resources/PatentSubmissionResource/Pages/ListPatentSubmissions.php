<?php

namespace App\Filament\Resources\PatentSubmissionResource\Pages;

use App\Filament\Resources\PatentSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListPatentSubmissions extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = PatentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PatentSubmissionsChart::class,
        ];
    }
}
