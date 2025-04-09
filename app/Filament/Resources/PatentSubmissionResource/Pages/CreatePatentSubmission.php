<?php

namespace App\Filament\Resources\PatentSubmissionResource\Pages;

use App\Filament\Resources\PatentSubmissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePatentSubmission extends CreateRecord
{
    protected static string $resource = PatentSubmissionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Make sure user_id is set if not already
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        
        return $data;
    }
}
