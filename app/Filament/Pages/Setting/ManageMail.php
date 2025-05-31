<?php

namespace App\Filament\Pages\Setting;

use App\Mail\TestMail;
use App\Settings\MailSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;

use function Filament\Support\is_app_url;

class ManageMail extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = MailSettings::class;

    protected static ?int $navigationSort = 99;
    protected static ?string $navigationIcon = 'fluentui-mail-settings-20';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->mutateFormDataBeforeFill(app(static::getSettings())->toArray());

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Configuration')
                            ->label(fn () => __('page.mail_settings.sections.config.title'))
                            ->description('Configure SMTP settings for sending emails. Leave fields empty to use environment defaults.')
                            ->icon('fluentui-calendar-settings-32-o')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('driver')->label(fn () => __('page.mail_settings.fields.driver'))
                                            ->options([
                                                "smtp" => "SMTP (Recommended)",
                                                "mailgun" => "Mailgun",
                                                "ses" => "Amazon SES",
                                                "postmark" => "Postmark",
                                            ])
                                            ->native(false)
                                            ->required()
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('host')->label(fn () => __('page.mail_settings.fields.host'))
                                            ->placeholder('e.g., smtp.gmail.com')
                                            ->helperText('SMTP server hostname'),
                                        Forms\Components\TextInput::make('port')->label(fn () => __('page.mail_settings.fields.port'))
                                            ->placeholder('587')
                                            ->helperText('Usually 587 for TLS or 465 for SSL'),
                                        Forms\Components\Select::make('encryption')->label(fn () => __('page.mail_settings.fields.encryption'))
                                            ->options([
                                                "ssl" => "SSL",
                                                "tls" => "TLS",
                                            ])
                                            ->native(false)
                                            ->helperText('TLS is recommended for Gmail'),
                                        Forms\Components\TextInput::make('timeout')->label(fn () => __('page.mail_settings.fields.timeout'))
                                            ->placeholder('60')
                                            ->helperText('Connection timeout in seconds'),
                                        Forms\Components\TextInput::make('username')->label(fn () => __('page.mail_settings.fields.username'))
                                            ->placeholder('your-email@gmail.com')
                                            ->helperText('Your email address'),
                                        Forms\Components\TextInput::make('password')->label(fn () => __('page.mail_settings.fields.password'))
                                            ->password()
                                            ->revealable()
                                            ->helperText('App password for Gmail (not your regular password)'),
                                    ])
                                    ->columns(3),
                            ])
                    ])
                    ->columnSpan([
                        "md" => 2
                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Current Status')
                            ->schema([
                                Forms\Components\Placeholder::make('environment_status')
                                    ->label('Environment Configuration')
                                    ->content(function () {
                                        $envHost = config('mail.mailers.smtp.host');
                                        $envUsername = config('mail.mailers.smtp.username');
                                        $envFromAddress = config('mail.from.address');
                                        
                                        $status = [];
                                        $status[] = $envHost ? '✅ SMTP Host: ' . $envHost : '❌ SMTP Host: Not configured';
                                        $status[] = $envUsername ? '✅ Username: Configured' : '❌ Username: Not configured';
                                        $status[] = $envFromAddress ? '✅ From Address: ' . $envFromAddress : '❌ From Address: Not configured';
                                        
                                        return new \Illuminate\Support\HtmlString(implode('<br>', $status));
                                    }),
                                Forms\Components\Placeholder::make('database_status')
                                    ->label('Database Configuration Status')
                                    ->content(function () {
                                        try {
                                            $settings = app(MailSettings::class);
                                            $isConfigured = $settings->isMailSettingsConfigured();
                                            
                                            if ($isConfigured) {
                                                return new \Illuminate\Support\HtmlString('✅ Database mail settings are fully configured');
                                            } else {
                                                return new \Illuminate\Support\HtmlString('⚠️ Database mail settings incomplete. Using environment defaults.');
                                            }
                                        } catch (\Exception $e) {
                                            return new \Illuminate\Support\HtmlString('❌ Error loading settings: ' . $e->getMessage());
                                        }
                                    }),
                            ]),
                        
                        Forms\Components\Section::make('From (Sender)')
                            ->label(fn () => __('page.mail_settings.section.sender.title'))
                            ->icon('fluentui-person-mail-48-o')
                            ->schema([
                                Forms\Components\TextInput::make('from_address')->label(fn () => __('page.mail_settings.fields.email'))
                                    ->placeholder('no-reply@yourdomain.com')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('from_name')->label(fn () => __('page.mail_settings.fields.name'))
                                    ->placeholder('PKKI ITERA')
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('Mail to')
                            ->label(fn () => __('page.mail_settings.section.mail_to.title'))
                            ->schema([
                                Forms\Components\TextInput::make('mail_to')
                                    ->label(fn () => __('page.mail_settings.fields.mail_to'))
                                    ->hiddenLabel()
                                    ->placeholder(fn () => __('page.mail_settings.fields.placeholder.receiver_email'))
                                    ->required(),
                                Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('Send Test Mail')
                                            ->label(fn () => __('page.mail_settings.actions.send_test_mail'))
                                            ->action('sendTestMail')
                                            ->color('warning')
                                            ->icon('fluentui-mail-alert-28-o')
                                    ])->fullWidth(),
                            ])
                    ])
                    ->columnSpan([
                        "md" => 1
                    ]),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function save(MailSettings $settings = null): void
    {
        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $settings->fill($data);
            $settings->save();

            $this->callHook('afterSave');

            $this->sendSuccessNotification('Mail Settings updated.');

            $this->redirect(static::getUrl(), navigate: FilamentView::hasSpaMode() && is_app_url(static::getUrl()));
        } catch (\Throwable $th) {
            $this->sendErrorNotification('Failed to update settings. '.$th->getMessage());
            throw $th;
        }
    }

    public function sendTestMail(MailSettings $settings = null)
    {
        $data = $this->form->getState();

        // Validate that essential fields are provided for testing
        $requiredFields = ['host', 'username', 'password', 'from_address'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $this->sendErrorNotification('Please fill in required fields: ' . implode(', ', $missingFields));
            return;
        }
        
        // Test with the provided configuration
        $settings->loadMailSettingsToConfig($data);
        
        try {
            $mailTo = $data['mail_to'];
            $mailData = [
                'title' => 'PKKI ITERA - Mail Configuration Test',
                'body' => 'This is a test email to verify your SMTP settings. If you receive this email, your mail configuration is working correctly!'
            ];

            Mail::to($mailTo)->send(new TestMail($mailData));

            $this->sendSuccessNotification('Test email sent successfully to: ' . $mailTo);
        } catch (\Exception $e) {
            $this->sendErrorNotification('Failed to send test email: ' . $e->getMessage());
        }
    }

    public function sendSuccessNotification($title)
    {
        Notification::make()
                ->title($title)
                ->success()
                ->send();
    }

    public function sendErrorNotification($title)
    {
        Notification::make()
                ->title($title)
                ->danger()
                ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.settings");
    }

    public static function getNavigationLabel(): string
    {
        return __("page.mail_settings.navigationLabel");
    }

    public function getTitle(): string|Htmlable
    {
        return __("page.mail_settings.title");
    }

    public function getHeading(): string|Htmlable
    {
        return __("page.mail_settings.heading");
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __("page.mail_settings.subheading");
    }
}
