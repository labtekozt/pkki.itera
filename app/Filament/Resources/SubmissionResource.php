<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use App\Models\SubmissionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'All Submissions';

    protected static ?string $navigationGroup = 'Intellectual Property';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => Auth::id()),

                        Forms\Components\Select::make('submission_type_id')
                            ->relationship('submissionType', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                $set('current_stage_id', null);
                            }),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'in_review' => 'In Review',
                                'revision_needed' => 'Revision Needed',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\TextInput::make('certificate')
                            ->maxLength(255)
                            ->placeholder('Certificate number (if issued)'),

                        Forms\Components\Select::make('current_stage_id')
                            ->relationship('currentStage', 'name', function (Builder $query, Get $get) {
                                $submissionTypeId = $get('submission_type_id');
                                if ($submissionTypeId) {
                                    $query->where('submission_type_id', $submissionTypeId)
                                        ->orderBy('order');
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->label('Current Stage')
                            ->visible(function (Get $get) {
                                return (bool) $get('submission_type_id');
                            }),
                    ])
                    ->columns(2),

                // Patent Form
                Forms\Components\Section::make('Patent Details')
                    ->schema([
                        Forms\Components\Select::make('patentDetail.patent_type')
                            ->options([
                                'utility' => 'Utility Patent',
                                'design' => 'Design Patent',
                                'plant' => 'Plant Patent',
                                'process' => 'Process Patent',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('patentDetail.invention_description')
                            ->label('Invention Description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('patentDetail.technical_field')
                            ->label('Technical Field'),

                        Forms\Components\Textarea::make('patentDetail.background')
                            ->label('Background'),

                        Forms\Components\Textarea::make('patentDetail.inventor_details')
                            ->label('Inventor Details')
                            ->required(),

                        Forms\Components\DatePicker::make('patentDetail.filing_date')
                            ->label('Filing Date'),

                        Forms\Components\TextInput::make('patentDetail.application_number')
                            ->label('Application Number'),

                        Forms\Components\DatePicker::make('patentDetail.publication_date')
                            ->label('Publication Date'),

                        Forms\Components\TextInput::make('patentDetail.publication_number')
                            ->label('Publication Number'),
                    ])
                    ->columns(2)
                    ->visible(function (Get $get) {
                        return $get('submission_type_id') &&
                            SubmissionType::find($get('submission_type_id'))?->slug === 'paten';
                    }),

                // Trademark Form
                Forms\Components\Section::make('Trademark Details')
                    ->schema([
                        Forms\Components\Select::make('trademarkDetail.trademark_type')
                            ->options([
                                'word' => 'Word Mark',
                                'design' => 'Design Mark',
                                'combined' => 'Combined Mark',
                                'sound' => 'Sound Mark',
                                'collective' => 'Collective Mark',
                                'certification' => 'Certification Mark',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('trademarkDetail.description')
                            ->label('Description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('trademarkDetail.goods_services_description')
                            ->label('Goods & Services Description')
                            ->required(),

                        Forms\Components\TextInput::make('trademarkDetail.nice_classes')
                            ->label('Nice Classification Classes')
                            ->required()
                            ->placeholder('e.g., 9, 42'),

                        Forms\Components\Checkbox::make('trademarkDetail.has_color_claim')
                            ->label('Has Color Claim?'),

                        Forms\Components\TextInput::make('trademarkDetail.color_description')
                            ->label('Color Description')
                            ->visible(function (Get $get) {
                                return $get('trademarkDetail.has_color_claim');
                            }),

                        Forms\Components\DatePicker::make('trademarkDetail.first_use_date')
                            ->label('Date of First Use'),

                        Forms\Components\TextInput::make('trademarkDetail.registration_number')
                            ->label('Registration Number'),

                        Forms\Components\DatePicker::make('trademarkDetail.registration_date')
                            ->label('Registration Date'),

                        Forms\Components\DatePicker::make('trademarkDetail.expiration_date')
                            ->label('Expiration Date'),
                    ])
                    ->columns(2)
                    ->visible(function (Get $get) {
                        return $get('submission_type_id') &&
                            SubmissionType::find($get('submission_type_id'))?->slug === 'brand';
                    }),

                // Copyright Form
                Forms\Components\Section::make('Copyright Details')
                    ->schema([
                        Forms\Components\Select::make('copyrightDetail.work_type')
                            ->options([
                                'literary' => 'Literary Work',
                                'musical' => 'Musical Work',
                                'dramatic' => 'Dramatic Work',
                                'artistic' => 'Artistic Work',
                                'audiovisual' => 'Audiovisual Work',
                                'sound_recording' => 'Sound Recording',
                                'architectural' => 'Architectural Work',
                                'computer_program' => 'Computer Program',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('copyrightDetail.work_description')
                            ->label('Work Description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('copyrightDetail.creation_year')
                            ->label('Year of Creation')
                            ->required()
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y')),

                        Forms\Components\Checkbox::make('copyrightDetail.is_published')
                            ->label('Is Published?'),

                        Forms\Components\DatePicker::make('copyrightDetail.publication_date')
                            ->label('Publication Date')
                            ->visible(function (Get $get) {
                                return $get('copyrightDetail.is_published');
                            }),

                        Forms\Components\TextInput::make('copyrightDetail.publication_place')
                            ->label('Place of Publication')
                            ->visible(function (Get $get) {
                                return $get('copyrightDetail.is_published');
                            }),

                        Forms\Components\Textarea::make('copyrightDetail.authors')
                            ->label('Authors'),

                        Forms\Components\Textarea::make('copyrightDetail.previous_registrations')
                            ->label('Previous Registrations'),

                        Forms\Components\Textarea::make('copyrightDetail.derivative_works')
                            ->label('Derivative Works'),

                        Forms\Components\TextInput::make('copyrightDetail.registration_number')
                            ->label('Registration Number'),

                        Forms\Components\DatePicker::make('copyrightDetail.registration_date')
                            ->label('Registration Date'),
                    ])
                    ->columns(2)
                    ->visible(function (Get $get) {
                        return $get('submission_type_id') &&
                            SubmissionType::find($get('submission_type_id'))?->slug === 'haki';
                    }),

                // Industrial Design Form
                Forms\Components\Section::make('Industrial Design Details')
                    ->schema([
                        Forms\Components\TextInput::make('industrialDesignDetail.design_type')
                            ->label('Design Type')
                            ->required(),

                        Forms\Components\Textarea::make('industrialDesignDetail.design_description')
                            ->label('Design Description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('industrialDesignDetail.novelty_statement')
                            ->label('Novelty Statement')
                            ->required(),

                        Forms\Components\Textarea::make('industrialDesignDetail.designer_information')
                            ->label('Designer Information')
                            ->required(),

                        Forms\Components\TextInput::make('industrialDesignDetail.locarno_class')
                            ->label('Locarno Classification'),

                        Forms\Components\DatePicker::make('industrialDesignDetail.filing_date')
                            ->label('Filing Date'),

                        Forms\Components\TextInput::make('industrialDesignDetail.application_number')
                            ->label('Application Number'),

                        Forms\Components\DatePicker::make('industrialDesignDetail.registration_date')
                            ->label('Registration Date'),

                        Forms\Components\TextInput::make('industrialDesignDetail.registration_number')
                            ->label('Registration Number'),

                        Forms\Components\DatePicker::make('industrialDesignDetail.expiration_date')
                            ->label('Expiration Date'),
                    ])
                    ->columns(2)
                    ->visible(function (Get $get) {
                        return $get('submission_type_id') &&
                            SubmissionType::find($get('submission_type_id'))?->slug === 'industrial_design';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submissionType.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.fullname')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'in_review' => 'warning',
                        'revision_needed' => 'danger',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label('Current Stage'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->label('Submission Type'),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'in_review' => 'In Review',
                        'revision_needed' => 'Revision Needed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('user_id')
                    ->relationship('user', 'fullname')
                    ->searchable()
                    ->preload()
                    ->label('Submitter'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextEntry::make('submissionType.name')
                            ->label('Submission Type'),

                        TextEntry::make('title')
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted' => 'info',
                                'in_review' => 'warning',
                                'revision_needed' => 'danger',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'completed' => 'success',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('currentStage.name')
                            ->label('Current Stage'),

                        TextEntry::make('certificate')
                            ->visible(fn($record) => (bool) $record->certificate),

                        TextEntry::make('user.fullname')
                            ->label('Submitted By'),

                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Submission Date'),
                    ])
                    ->columns(2),

                // Patent Details Section
                Section::make('Patent Details')
                    ->schema([
                        TextEntry::make('patentDetail.patent_type')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'utility' => 'Utility Patent',
                                'design' => 'Design Patent',
                                'plant' => 'Plant Patent',
                                'process' => 'Process Patent',
                                default => $state,
                            }),

                        TextEntry::make('patentDetail.invention_description')
                            ->columnSpanFull(),

                        TextEntry::make('patentDetail.technical_field')
                            ->columnSpanFull()
                            ->visible(fn($record) => isset($record->patentDetail->technical_field)),

                        TextEntry::make('patentDetail.background')
                            ->columnSpanFull()
                            ->visible(fn($record) => isset($record->patentDetail->background)),

                        TextEntry::make('patentDetail.inventor_details')
                            ->columnSpanFull(),

                        TextEntry::make('patentDetail.filing_date')
                            ->date()
                            ->visible(fn($record) => isset($record->patentDetail->filing_date)),

                        TextEntry::make('patentDetail.application_number')
                            ->visible(fn($record) => isset($record->patentDetail->application_number)),

                        TextEntry::make('patentDetail.publication_date')
                            ->date()
                            ->visible(fn($record) => isset($record->patentDetail->publication_date)),

                        TextEntry::make('patentDetail.publication_number')
                            ->visible(fn($record) => isset($record->patentDetail->publication_number)),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record->submissionType->slug === 'paten' && $record->patentDetail),

                // Similar sections for other submission types would follow here
                // I'll include one more example for Trademark

                Section::make('Trademark Details')
                    ->schema([
                        TextEntry::make('trademarkDetail.trademark_type')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'word' => 'Word Mark',
                                'design' => 'Design Mark',
                                'combined' => 'Combined Mark',
                                'sound' => 'Sound Mark',
                                'collective' => 'Collective Mark',
                                'certification' => 'Certification Mark',
                                default => $state,
                            }),

                        TextEntry::make('trademarkDetail.description')
                            ->columnSpanFull(),

                        TextEntry::make('trademarkDetail.goods_services_description')
                            ->columnSpanFull(),

                        TextEntry::make('trademarkDetail.nice_classes')
                            ->label('Nice Classification Classes'),

                        TextEntry::make('trademarkDetail.has_color_claim')
                            ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : 'No')
                            ->label('Has Color Claim'),

                        TextEntry::make('trademarkDetail.color_description')
                            ->visible(fn($record) => isset($record->trademarkDetail->color_description)),

                        TextEntry::make('trademarkDetail.first_use_date')
                            ->date()
                            ->visible(fn($record) => isset($record->trademarkDetail->first_use_date)),

                        TextEntry::make('trademarkDetail.registration_number')
                            ->visible(fn($record) => isset($record->trademarkDetail->registration_number)),

                        TextEntry::make('trademarkDetail.registration_date')
                            ->date()
                            ->visible(fn($record) => isset($record->trademarkDetail->registration_date)),

                        TextEntry::make('trademarkDetail.expiration_date')
                            ->date()
                            ->visible(fn($record) => isset($record->trademarkDetail->expiration_date)),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record->submissionType->slug === 'brand' && $record->trademarkDetail),

                // Add similar sections for Copyright and Industrial Design
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\SubmissionResource\RelationManagers\DocumentsRelationManager::class,
            \App\Filament\Resources\SubmissionResource\RelationManagers\TrackingHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'create' => Pages\CreateSubmission::route('/create'),
            'view' => Pages\ViewSubmission::route('/{record}'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }
}
