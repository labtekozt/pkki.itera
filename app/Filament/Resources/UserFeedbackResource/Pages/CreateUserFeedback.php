<?php

namespace App\Filament\Resources\UserFeedbackResource\Pages;

use App\Filament\Resources\UserFeedbackResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUserFeedback extends CreateRecord
{
    protected static string $resource = UserFeedbackResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Feedback Created')
            ->body('New user feedback has been recorded successfully.')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->label('View Feedback')
                    ->url($this->getRedirectUrl()),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill user if not set and user is authenticated
        if (empty($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        // Add browser information if available
        if (empty($data['browser_info']) && request()->header('User-Agent')) {
            $data['browser_info'] = [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
            ];
        }

        return $data;
    }
}
