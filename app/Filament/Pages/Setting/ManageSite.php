<?php

namespace App\Filament\Pages\Setting;

use App\Settings\SiteSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;

use function Filament\Support\is_app_url;

class ManageSite extends SettingsPage
{
    use HasPageShield;
    protected static string $settings = SiteSettings::class;

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

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
        $settings = app(static::getSettings());

        $data = $this->mutateFormDataBeforeFill($settings->toArray());

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('General website configuration')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\Toggle::make('is_maintenance')
                                ->label(__('resource.settings.maintenance_mode'))
                                ->helperText(__('resource.settings.maintenance_helper'))
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label(__('resource.settings.site_name'))
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('tagline')
                                ->label(__('resource.settings.site_tagline'))
                                ->helperText(__('resource.settings.site_helper_tagline'))
                                ->maxLength(150),
                            Forms\Components\Textarea::make('description')
                                ->label(__('resource.settings.site_description'))
                                ->helperText(__('resource.settings.site_helper_description'))
                                ->rows(3)
                                ->maxLength(500),
                        ])->columns(2),
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('resource.settings.site_logo'))
                            ->image()
                            ->directory('sites')
                            ->visibility('public')
                            ->imagePreviewHeight('100')
                            ->maxSize(1024)
                            ->helperText(__('resource.settings.logo_helper')),
                    ]),

                Forms\Components\Section::make(__('resource.settings.company_information'))
                    ->description(__('resource.settings.company_description'))
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('company_name')
                                ->label(__('resource.settings.company_name'))
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('company_email')
                                ->label(__('resource.settings.company_email'))
                                ->email()
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('company_phone')
                                ->label(__('resource.settings.company_phone'))
                                ->tel()
                                ->maxLength(20),
                            Forms\Components\Textarea::make('company_address')
                                ->label(__('resource.settings.company_address'))
                                ->rows(2)
                                ->maxLength(200),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make(__('resource.settings.regional_settings'))
                    ->description(__('resource.settings.language_time_settings'))
                    ->icon('heroicon-o-globe-alt')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\Select::make('default_language')
                                ->label(__('resource.settings.default_language'))
                                ->options([
                                    'en' => __('resource.settings.languages.english'),
                                    'fr' => __('resource.settings.languages.french'),
                                    'es' => __('resource.settings.languages.spanish'),
                                    'de' => __('resource.settings.languages.german'),
                                    'it' => __('resource.settings.languages.italian'),
                                    'pt' => __('resource.settings.languages.portuguese'),
                                    'ru' => __('resource.settings.languages.russian'),
                                    'zh' => __('resource.settings.languages.chinese'),
                                    'ja' => __('resource.settings.languages.japanese'),
                                    'ar' => __('resource.settings.languages.arabic'),
                                ])
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('timezone')
                                ->label(__('resource.settings.timezone'))
                                ->options(function () {
                                    $timezones = [];
                                    foreach (timezone_identifiers_list() as $timezone) {
                                        $timezones[$timezone] = $timezone;
                                    }
                                    return $timezones;
                                })
                                ->searchable()
                                ->required(),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make(__('resource.settings.legal_information'))
                    ->description(__('resource.settings.legal_description'))
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('copyright_text')
                                ->label(__('resource.settings.copyright_text'))
                                ->maxLength(200),
                            Forms\Components\TextInput::make('terms_url')
                                ->label(__('resource.settings.terms_url'))
                                ->maxLength(100)
                                ->prefix(function (Forms\Get $get) {
                                    return url('/');
                                }),
                            Forms\Components\TextInput::make('privacy_url')
                                ->label(__('resource.settings.privacy_url'))
                                ->maxLength(100)
                                ->prefix(function (Forms\Get $get) {
                                    return url('/');
                                }),
                            Forms\Components\TextInput::make('cookie_policy_url')
                                ->label(__('resource.settings.cookie_policy_url'))
                                ->maxLength(100)
                                ->prefix(function (Forms\Get $get) {
                                    return url('/');
                                }),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make(__('resource.settings.error_messages'))
                    ->description(__('resource.settings.error_messages_description'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\Textarea::make('custom_404_message')
                                ->label(__('resource.settings.custom_404_message'))
                                ->rows(2)
                                ->maxLength(500),
                            Forms\Components\Textarea::make('custom_500_message')
                                ->label(__('resource.settings.custom_500_message'))
                                ->rows(2)
                                ->maxLength(500),
                        ])->columns(2),
                    ]),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->mutateFormDataBeforeSave($this->form->getState());

            $settings = app(static::getSettings());

            $settings->fill($data);
            $settings->save();

            Notification::make()
                ->title('Settings saved successfully!')
                ->body('Your site general settings have been updated.')
                ->success()
                ->send();

            $this->redirect(static::getUrl(), navigate: FilamentView::hasSpaMode() && is_app_url(static::getUrl()));
        } catch (\Throwable $th) {
            Notification::make()
                ->title('Error saving settings')
                ->body($th->getMessage())
                ->danger()
                ->send();

            throw $th;
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.sites");
    }

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Site Settings';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Site Settings';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage your website\'s general configuration';
    }
}
