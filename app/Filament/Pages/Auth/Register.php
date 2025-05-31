<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\UserDetail;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Support\Htmlable;

class Register extends BaseRegister
{
    protected static string $view = 'filament.pages.auth.register';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Akun')
                    ->description('Isi informasi dasar untuk akun Anda')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
                
                Section::make('Informasi Personal')
                    ->description('Isi informasi personal dan kontak Anda')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phonenumber')
                                    ->label('No. Telepon')
                                    ->tel()
                                    ->placeholder('+62 812 3456 7890')
                                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                    ->maxLength(255)
                                    ->nullable(),
                            ]),
                        
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->placeholder('Masukkan alamat lengkap Anda')
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
                
                Section::make('Informasi Akademik')
                    ->description('Isi informasi terkait pendidikan Anda')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('jurusan')
                                    ->label('Jurusan')
                                    ->placeholder('contoh: Teknik Informatika')
                                    ->maxLength(255)
                                    ->nullable(),
                                
                                TextInput::make('prodi')
                                    ->label('Program Studi')
                                    ->placeholder('contoh: Sistem Informasi')
                                    ->maxLength(255)
                                    ->nullable(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('fullname')
            ->label('Nama Lengkap')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required()
            ->minLength(8)
            ->maxLength(255)
            ->same('passwordConfirmation')
            ->validationAttribute('password')
            ->helperText('Password minimal 8 karakter');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Password')
            ->password()
            ->revealable()
            ->required()
            ->maxLength(255)
            ->dehydrated(false)
            ->validationAttribute('konfirmasi password');
    }

    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        // Create the user
        $user = User::create([
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'provider' => 'local',
        ]);

        // Create user details if any additional information is provided
        if (
            !empty($data['phonenumber']) || 
            !empty($data['alamat']) || 
            !empty($data['jurusan']) || 
            !empty($data['prodi'])
        ) {
            UserDetail::create([
                'user_id' => $user->id,
                'phonenumber' => $data['phonenumber'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'jurusan' => $data['jurusan'] ?? null,
                'prodi' => $data['prodi'] ?? null,
            ]);
        }

        // Assign default role if needed
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            // Try to assign a default role - 'civitas' seems appropriate for ITERA users
            $defaultRole = \Spatie\Permission\Models\Role::where('name', 'civitas')->first();
            if ($defaultRole) {
                $user->assignRole($defaultRole);
            }
        }

        // Return the registration response
        return app(RegistrationResponse::class);
    }

    public function getHeading(): string | Htmlable
    {
        return 'Daftar Akun Baru';
    }

    public function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    public function getRegisterFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('register')
            ->label('Daftar')
            ->submit('register');
    }
}
