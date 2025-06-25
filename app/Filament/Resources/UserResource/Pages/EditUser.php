<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\UserDetail;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Support;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use JoseEspinal\RecordNavigation\Traits\HasRecordNavigation;

class EditUser extends EditRecord
{
    use HasRecordNavigation;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\ActionGroup::make([
                Actions\EditAction::make()
                    ->label('Change password')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->revealable()
                            ->required(),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->password()
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->revealable()
                            ->same('password')
                            ->required(),
                    ])
                    ->modalWidth(Support\Enums\MaxWidth::Medium)
                    ->modalHeading('Update Password')
                    ->modalDescription(fn($record) => $record->email)
                    ->modalAlignment(Alignment::Center)
                    ->modalCloseButton(false)
                    ->modalSubmitActionLabel('Submit')
                    ->modalCancelActionLabel('Cancel'),

                Actions\DeleteAction::make()
                    ->extraAttributes(["class" => "border-b"]),

                Actions\CreateAction::make()
                    ->label(__('resource.create_user'))
                    ->url(fn(): string => static::$resource::getNavigationUrl() . '/create'),
            ])
            ->icon('heroicon-m-ellipsis-horizontal')
            ->hiddenLabel()
            ->button()
            ->tooltip('More Actions')
            ->color('gray')
        ];

        return array_merge($this->getNavigationActions(), $actions);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fetch the related UserDetail if it exists
        $userDetail = $this->record->detail;
        
        if ($userDetail) {
            // Add UserDetail data to the form data
            $data['detail'] = [
                'alamat' => $userDetail->alamat,
                'phonenumber' => $userDetail->phonenumber,
                'prodi' => $userDetail->prodi,
                'jurusan' => $userDetail->jurusan,
            ];
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Get the detail data from the form
        $detailData = $this->data['detail'] ?? [];
        
        // Find or create UserDetail for this user
        $userDetail = $this->record->detail ?? new UserDetail();
        
        // Update UserDetail fields with null handling
        $userDetail->user_id = $this->record->id;
        $userDetail->alamat = $detailData['alamat'] ?? $userDetail->alamat ?? null;
        $userDetail->phonenumber = $detailData['phonenumber'] ?? $userDetail->phonenumber ?? null;
        $userDetail->prodi = $detailData['prodi'] ?? $userDetail->prodi ?? null;
        $userDetail->jurusan = $detailData['jurusan'] ?? $userDetail->jurusan ?? null;
        
        // Save the UserDetail
        $userDetail->save();
    }

    public function getTitle(): string|Htmlable
    {
        $title = $this->record->fullname ?? $this->record->name;
        $badge = $this->getBadgeStatus();

        return new HtmlString("
            <div class='flex items-center space-x-2'>
                <div>$title</div>
                $badge
            </div>
        ");
    }

    public function getBadgeStatus(): string|Htmlable
    {
        if (empty($this->record->email_verified_at)) {
            $badge = "<span class='inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md text-danger-700 bg-danger-50 ring-1 ring-inset ring-danger-600/20'>Unverified</span>";
        } else {
            $badge = "<span class='inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md text-success-700 bg-success-50 ring-1 ring-inset ring-success-600/20'>Verified</span>";
        }

        return $badge;
    }
}
