<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditSubmissionType extends EditRecord
{
    protected static string $resource = SubmissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('requirements')
                ->label('Manage Requirements')
                ->url(fn () => $this->getResource()::getUrl('requirements', ['record' => $this->record])),
            
            Actions\Action::make('stages')
                ->label('Manage Stages')
                ->url(fn () => $this->getResource()::getUrl('stages', ['record' => $this->record])),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
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
