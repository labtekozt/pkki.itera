<?php

namespace App\Filament\Resources\UserFeedbackResource\Pages;

use App\Filament\Resources\UserFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUserFeedback extends EditRecord
{
    protected static string $resource = UserFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            
            Actions\Action::make('mark_processed')
                ->label('Mark as Processed')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => !$this->record->processed_at)
                ->requiresConfirmation()
                ->modalDescription('This will mark the feedback as reviewed and processed.')
                ->action(function () {
                    $this->record->update([
                        'processed_at' => now(),
                        'processed_by' => auth()->id(),
                    ]);
                    
                    Notification::make()
                        ->success()
                        ->title('Feedback Processed')
                        ->body('The feedback has been marked as processed.')
                        ->send();
                }),
                
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this feedback? This action cannot be undone.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Feedback Updated')
            ->body('The feedback has been updated successfully.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Add timestamp when admin notes are updated
        if (isset($data['admin_notes']) && $data['admin_notes'] !== $this->record->admin_notes) {
            if (!empty($data['admin_notes']) && empty($this->record->admin_notes)) {
                $data['admin_notes'] = "[" . now()->format('Y-m-d H:i') . "] " . auth()->user()->fullname . ": " . $data['admin_notes'];
            }
        }

        return $data;
    }
}
