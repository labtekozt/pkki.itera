<?php

namespace App\Filament\Resources;

use App\Filament\Forms\SubmissionFormFactory;
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
                Forms\Components\Tabs::make('Submission')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn() => Auth::id()),

                                Forms\Components\Select::make('submission_type_id')
                                    ->relationship('submissionType', 'name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('current_stage_id', null);

                                        if ($state) {
                                            $submissionType = SubmissionType::find($state);
                                        }
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
                                    ->placeholder('Certificate number (if issued)')
                                    ->visible(fn(Get $get) => in_array($get('status'), ['approved', 'completed'])),

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
                                        return (bool) $get('submission_type_id') && $get('status') !== 'draft';
                                    }),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Type Details')
                            ->schema(function (Get $get) {
                                $submissionTypeId = $get('submission_type_id');

                                if (!$submissionTypeId) {
                                    return [
                                        Forms\Components\Placeholder::make('select_type')
                                            ->content('Please select a submission type first')
                                            ->columnSpanFull(),
                                    ];
                                }

                                $submissionType = SubmissionType::find($submissionTypeId);

                                if (!$submissionType) {
                                    return [];
                                }

                                return SubmissionFormFactory::getFormForSubmissionType($submissionType->slug);
                            })
                            ->visible(fn(Get $get) => (bool) $get('submission_type_id')),
                    ])
                    ->columnSpanFull(),
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
                Tables\Actions\EditAction::make()
                    ->label('Edit Submission')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Interface lengkap dengan semua fitur'),
    
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
            ->schema(function ($record) {
                return self::getInfolistSchema($record);
            });
    }

    /**
     * Get the infolist schema for a submission record
     * This is separated so it can be reused in the ViewSubmission page
     */
    public static function getInfolistSchema($record): array
    {
        return [
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

                    TextEntry::make('reviewer_notes')
                        ->label('Reviewer Notes')
                        ->markdown()
                        ->columnSpanFull()
                        ->visible(fn($record) => !empty($record->reviewer_notes) && in_array($record->status, ['revision_needed', 'rejected'])),
                    
                    TextEntry::make('currentStage.name')
                        ->label('Current Stage')
                        ->visible(fn($record) => !empty($record->current_stage_id)),

                    TextEntry::make('certificate')
                        ->visible(fn($record) => !empty($record->certificate)),

                    TextEntry::make('user.fullname')
                        ->label('Submitted By'),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label('Submission Date'),

                    TextEntry::make('updated_at')
                        ->dateTime()
                        ->label('Last Updated')
                        ->visible(fn($record) => $record->updated_at->ne($record->created_at)),
                ])
                ->columns(2)
                ->collapsible(false),

            // Type-specific section
            Section::make('Type Details')
                ->schema(function () use ($record) {
                    if (!$record->submissionType) {
                        return [];
                    }

                    return match ($record->submissionType->slug) {
                        'paten' => self::getPatentInfolist($record),
                        'brand' => self::getBrandInfolist($record),
                        'haki' => self::getHakiInfolist($record),
                        'industrial_design' => self::getIndustrialDesignInfolist($record),
                        default => [],
                    };
                })
                ->columns(2)
                ->visible(fn($record) => $record->submissionType !== null)
                ->collapsible(),
        ];
    }

    private static function getPatentInfolist($record): array
    {
        if (!$record->patentDetail) {
            return [];
        }

        return [
            TextEntry::make('patentDetail.application_type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'simple_patent' => 'Simple Patent',
                    'patent' => 'Standard Patent',
                    default => $state,
                }),

            TextEntry::make('patentDetail.patent_title')
                ->label('Patent Title')
                ->columnSpanFull(),

            TextEntry::make('patentDetail.patent_description')
                ->label('Description')
                ->columnSpanFull(),

            TextEntry::make('patentDetail.from_grant_research')
                ->label('From Grant Research')
                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

            TextEntry::make('patentDetail.self_funded')
                ->label('Self Funded')
                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

            TextEntry::make('patentDetail.inventors_name')
                ->label('Inventors'),

            TextEntry::make('patentDetail.media_link')
                ->label('Media Link')
                ->visible(fn($record) => !empty($record->patentDetail->media_link)),
        ];
    }

    private static function getBrandInfolist($record): array
    {
        if (!$record->brandDetail) {
            return [];
        }

        return [
            TextEntry::make('brandDetail.brand_name')
                ->label('Brand Name'),

            TextEntry::make('brandDetail.brand_type')
                ->label('Brand Type'),

            TextEntry::make('brandDetail.brand_description')
                ->label('Description')
                ->columnSpanFull(),

            TextEntry::make('brandDetail.inovators_name')
                ->label('Innovators'),

            TextEntry::make('brandDetail.application_type')
                ->label('Application Type'),

            TextEntry::make('brandDetail.nice_classes')
                ->label('Nice Classification')
                ->visible(fn($record) => !empty($record->brandDetail->nice_classes)),

            TextEntry::make('brandDetail.goods_services_search')
                ->label('Goods & Services')
                ->columnSpanFull()
                ->visible(fn($record) => !empty($record->brandDetail->goods_services_search)),
        ];
    }

    private static function getHakiInfolist($record): array
    {
        if (!$record->hakiDetail) {
            return [];
        }

        return [
            TextEntry::make('hakiDetail.haki_title')
                ->label('Work Title')
                ->columnSpanFull(),

            TextEntry::make('hakiDetail.work_type')
                ->label('Work Type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'literary' => 'Literary Work',
                    'musical' => 'Musical Work',
                    'dramatic' => 'Dramatic Work',
                    'artistic' => 'Artistic Work',
                    'audiovisual' => 'Audiovisual Work',
                    'sound_recording' => 'Sound Recording',
                    'computer_program' => 'Computer Program',
                    default => $state,
                }),

            TextEntry::make('hakiDetail.work_description')
                ->label('Work Description')
                ->columnSpanFull(),

            TextEntry::make('hakiDetail.inventors_name')
                ->label('Creators/Authors'),

            TextEntry::make('hakiDetail.registration_number')
                ->label('Registration Number')
                ->visible(fn($record) => !empty($record->hakiDetail->registration_number)),

            TextEntry::make('hakiDetail.registration_date')
                ->label('Registration Date')
                ->date()
                ->visible(fn($record) => !empty($record->hakiDetail->registration_date)),
        ];
    }

    private static function getIndustrialDesignInfolist($record): array
    {
        if (!$record->industrialDesignDetail) {
            return [];
        }

        return [
            TextEntry::make('industrialDesignDetail.design_title')
                ->label('Design Title')
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.design_type')
                ->label('Design Type'),

            TextEntry::make('industrialDesignDetail.design_description')
                ->label('Design Description')
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.novelty_statement')
                ->label('Novelty Statement')
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.inventors_name')
                ->label('Inventors'),

            TextEntry::make('industrialDesignDetail.designer_information')
                ->label('Designer Information'),

            TextEntry::make('industrialDesignDetail.locarno_class')
                ->label('Locarno Classification')
                ->visible(fn($record) => !empty($record->industrialDesignDetail->locarno_class)),
        ];
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
            'process' => Pages\ProcessSubmission::route('/{record}/process'),
        ];
    }
}
