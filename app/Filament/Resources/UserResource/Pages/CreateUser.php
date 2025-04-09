<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Settings\MailSettings;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateUser extends CreateRecord
{
    use HasWizard;

    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Account Information')
                ->description('Basic account details')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('media')
                        ->collection('avatars')
                        ->avatar()
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('fullname')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique('users', 'email'),
                            TextInput::make('password')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->dehydrateStateUsing(fn($state) => bcrypt($state)),
                            TextInput::make('password_confirmation')
                                ->password()
                                ->required()
                                ->same('password')
                                ->dehydrated(false),
                        ]),
                ]),

            Step::make('User Details')
                ->description('Personal information')
                ->schema([
                    TextInput::make('userDetail.phonenumber')
                        ->label("No. HP")
                        ->tel()
                        ->nullable(),
                    Textarea::make('userDetail.alamat')
                        ->label('Alamat')
                        ->nullable()
                        ->columnSpanFull(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('userDetail.jurusan')
                                ->label('Jurusan')
                                ->nullable(),
                            TextInput::make('userDetail.prodi')
                                ->label('Program Studi')
                                ->nullable(),
                        ]),
                ]),

            Step::make('Role & Permissions')
                ->description('User role assignment')
                ->schema([
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->preload()
                        ->required(),
                ]),
        ];
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $settings = app(MailSettings::class);

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        // Create user details if provided in the wizard
        if (isset($this->data['userDetail'])) {
            $user->detail()->create([
                'alamat' => $this->data['userDetail']['alamat'] ?? null,
                'phonenumber' => $this->data['userDetail']['phonenumber'] ?? null,
                'prodi' => $this->data['userDetail']['prodi'] ?? null,
                'jurusan' => $this->data['userDetail']['jurusan'] ?? null,
            ]);
        }

        $user->save();

        if ($settings->isMailSettingsConfigured()) {
            $notification = new VerifyEmail();
            $notification->url = Filament::getVerifyEmailUrl($user);

            $settings->loadMailSettingsToConfig();

            $user->notify($notification);

            Notification::make()
                ->title(__('resource.user.notifications.verify_sent.title'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('resource.user.notifications.verify_warning.title'))
                ->body(__('resource.user.notifications.verify_warning.description'))
                ->warning()
                ->send();
        }
    }
}
