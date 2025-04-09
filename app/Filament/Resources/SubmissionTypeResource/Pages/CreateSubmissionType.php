<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateSubmissionType extends CreateRecord
{
    protected static string $resource = SubmissionTypeResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
