<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Settings\MailSettings;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static int $globalSearchResultsLimit = 20;

    protected static ?int $navigationSort = -1;
    protected static ?string $navigationIcon = 'heroicon-s-users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('resource.user.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('resource.user.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.user.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('media')
                            ->hiddenLabel()
                            ->avatar()
                            ->collection('avatars')
                            ->alignCenter()
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Action::make('resend_verification')
                                ->label(__('resource.user.actions.resend_verification'))
                                ->color('info')
                                ->action(fn(MailSettings $settings, Model $record) => static::doResendEmailVerification($settings, $record)),
                        ])
                            ->hiddenOn('create')
                            ->fullWidth(),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label(__('resource.user.fields.password'))
                                    ->password()
                                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                                    ->dehydrated(fn(?string $state): bool => filled($state))
                                    ->revealable()
                                    ->placeholder(__('resource.user_password_placeholder'))
                                    ->required(),
                                Forms\Components\TextInput::make('passwordConfirmation')
                                    ->label(__('resource.user.fields.password_confirmation'))
                                    ->password()
                                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                                    ->dehydrated(fn(?string $state): bool => filled($state))
                                    ->revealable()
                                    ->same('password')
                                    ->required(),
                            ])
                            ->compact()
                            ->hidden(fn(string $operation): bool => $operation === 'edit'),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Placeholder::make('email_verified_at')
                                    ->label(__('resource.general.email_verified_at'))
                                    ->content(fn(User $record): ?string => new HtmlString("$record->email_verified_at")),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label(__('resource.general.created_at'))
                                    ->content(fn(User $record): ?string => $record->created_at?->diffForHumans()),
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label(__('resource.general.updated_at'))
                                    ->content(fn(User $record): ?string => $record->updated_at?->diffForHumans()),
                            ])
                            ->compact()
                            ->hidden(fn(string $operation): bool => $operation === 'create'),
                        Forms\Components\Section::make(__('resource.user.sections.provider_info'))
                            ->schema([
                                Forms\Components\Placeholder::make('provider')
                                    ->label(__('resource.user.fields.provider'))
                                    ->content(fn(User $record): ?string => new HtmlString("$record->provider")),
                            ])
                            ->compact()
                            ->hidden(fn(string $operation): bool => $operation === 'create'),
                    ])
                    ->columnSpan(1),

                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make(__('resource.user.tabs.details'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('fullname')
                                    ->label(__('resource.user.fields.fullname'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->placeholder(__('resource.user.placeholders.full_name'))
                                    ->dehydrated(fn(?string $state): bool => filled($state)),

                                Forms\Components\TextInput::make('email')
                                    ->label(__('resource.user.fields.email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('resource.user_email_placeholder'))
                                    ->unique(
                                        table: 'users',
                                        column: 'email',
                                        ignorable: fn($record) => $record
                                    ),

                                // User Detail Section
                                Forms\Components\Section::make(__('resource.user.sections.user_details'))
                                    ->schema([
                                        Forms\Components\TextInput::make('detail.phonenumber')
                                            ->label(__('resource.user.fields.phone_number'))
                                            ->tel()
                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                            ->placeholder(__('resource.user.placeholders.phone_number'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('detail.alamat')
                                            ->label(__('resource.user.fields.address'))
                                            ->placeholder(__('resource.user.placeholders.address'))
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->maxLength(255),


                                        // Academic Information subsection
                                        Forms\Components\Fieldset::make(__('resource.user.sections.academic_info'))
                                            ->schema([
                                                Forms\Components\TextInput::make('detail.jurusan')
                                                    ->label(__('resource.user.fields.department'))
                                                    ->placeholder(__('resource.user.placeholders.department'))
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('detail.prodi')
                                                    ->label(__('resource.user.fields.program_studi'))
                                                    ->placeholder(__('resource.user.placeholders.program_studi'))
                                                    ->maxLength(255),
                                            ])
                                            ->columns(2),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('resource.user.tabs.roles'))
                            ->icon('fluentui-shield-task-48')
                            ->visible(fn() => auth()->user()->isSuperAdmin())
                            ->schema([
                                Select::make('roles')
                                    ->label(__('resource.user.fields.roles'))
                                    ->relationship('roles', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(Model $record) => Str::headline($record->name))
                                    ->preload()
                                    ->searchable()
                                    ->disabled(fn() => !auth()->user()->isSuperAdmin())
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 2
                    ]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('media')->label(__('resource.user.columns.avatar'))
                    ->collection('avatars')
                    ->wrap(),
                Tables\Columns\TextColumn::make('fullname')->label(__('resource.user.columns.fullname'))
                    ->description(fn(Model $record) => $record->firstname . ' ' . $record->lastname)
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label(__('resource.user.columns.role'))
                    ->formatStateUsing(fn($state): string => Str::headline($state))
                    ->colors(['info'])
                    ->badge(),
                Tables\Columns\TextColumn::make('email')->label(__('resource.user.columns.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')->default('local')
                    ->label(__('resource.user.columns.provider'))
                    ->formatStateUsing(fn($state): string => Str::headline($state))
                    ->colors(['info'])
                    ->badge(),
                Tables\Columns\TextColumn::make('email_verified_at')->label(__('resource.user.columns.verified_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('resource.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label(__('resource.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data) {
                        // Remove roles data for non-super admins to prevent changes
                        if (!auth()->user()->isSuperAdmin()) {
                            unset($data['roles']);
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->email;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'fullname'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'name' => $record->fullname,
            'email' => $record->email,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.access");
    }

    public static function doResendEmailVerification($settings = null, $user): void
    {
        if (!method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

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

    public static function getBasicFormSchema(): array
    {
        return [
            TextInput::make('fullname')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->unique(table: static::$model, ignorable: fn($record) => $record)
                ->maxLength(255),

            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                ->dehydrated(fn(?string $state): bool => filled($state))
                ->required(fn(string $operation): bool => $operation === 'create')
                ->maxLength(255),
        ];
    }
}
