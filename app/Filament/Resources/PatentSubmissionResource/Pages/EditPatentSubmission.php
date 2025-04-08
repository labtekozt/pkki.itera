<?php

namespace App\Filament\Resources\PatentSubmissionResource\Pages;

use App\Filament\Resources\PatentSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatentSubmission extends EditRecord
{
    protected static string $resource = PatentSubmissionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
