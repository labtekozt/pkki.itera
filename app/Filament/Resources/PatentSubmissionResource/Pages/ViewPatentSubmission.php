<?php

namespace App\Filament\Resources\PatentSubmissionResource\Pages;

use App\Filament\Resources\PatentSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatentSubmission extends ViewRecord
{
    protected static string $resource = PatentSubmissionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
