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

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('resource.submission.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('menu.nav_group.intellectual_property');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Submission')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('resource.submission.tabs.basic_info'))
                            ->schema([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn() => Auth::id()),

                                Forms\Components\Select::make('submission_type_id')
                                    ->relationship('submissionType', 'name')
                                    ->label(__('resource.submission.fields.submission_type'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('current_stage_id', null);

                                        if ($state) {
                                            $submissionType = SubmissionType::find($state);
                                        }
                                    }),

                                Forms\Components\TextInput::make('title')
                                    ->label(__('resource.submission.fields.title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('status')
                                    ->label(__('resource.submission.fields.status'))
                                    ->options([
                                        'draft' => __('resource.submission.status.draft'),
                                        'submitted' => __('resource.submission.status.submitted'),
                                        'in_review' => __('resource.submission.status.in_review'),
                                        'revision_needed' => __('resource.submission.status.revision_needed'),
                                        'approved' => __('resource.submission.status.approved'),
                                        'rejected' => __('resource.submission.status.rejected'),
                                        'completed' => __('resource.submission.status.completed'),
                                        'cancelled' => __('resource.submission.status.cancelled'),
                                    ])
                                    ->default('draft')
                                    ->required(),                        Forms\Components\TextInput::make('certificate')
                            ->label(__('resource.submission.fields.certificate'))
                            ->maxLength(255)
                            ->placeholder(__('resource.submission.placeholders.certificate'))
                            ->visible(fn(Get $get) => in_array($get('status'), ['approved', 'completed'])),                        Forms\Components\Select::make('current_stage_id')
                            ->relationship('currentStage', 'name', function (Builder $query, Get $get) {
                                $submissionTypeId = $get('submission_type_id');
                                if ($submissionTypeId) {
                                    $query->where('submission_type_id', $submissionTypeId)
                                        ->orderBy('order');
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->label(__('resource.submission.fields.current_stage'))
                            ->visible(function (Get $get) {
                                return (bool) $get('submission_type_id') && $get('status') !== 'draft';
                            }),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make(__('resource.submission.tabs.details'))
                            ->schema(function (Get $get) {
                                $submissionTypeId = $get('submission_type_id');

                                if (!$submissionTypeId) {
                                    return [
                                        Forms\Components\Placeholder::make('select_type')
                                            ->content(__('resource.submission.placeholders.select_type_first'))
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
                    ->label(__('resource.submission.fields.submission_type'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('resource.submission.fields.title'))
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.fullname')
                    ->label(__('resource.submission.fields.user'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('resource.submission.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("resource.submission.status.{$state}"))
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
                    ->label(__('resource.submission.fields.current_stage')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.submission.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resource.general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->label(__('resource.submission.fields.submission_type')),

                SelectFilter::make('status')
                    ->label(__('resource.submission.fields.status'))
                    ->options([
                        'draft' => __('resource.submission.status.draft'),
                        'submitted' => __('resource.submission.status.submitted'),
                        'in_review' => __('resource.submission.status.in_review'),
                        'revision_needed' => __('resource.submission.status.revision_needed'),
                        'approved' => __('resource.submission.status.approved'),
                        'rejected' => __('resource.submission.status.rejected'),
                        'completed' => __('resource.submission.status.completed'),
                        'cancelled' => __('resource.submission.status.cancelled'),
                    ]),

                SelectFilter::make('user_id')
                    ->relationship('user', 'fullname')
                    ->searchable()
                    ->preload()
                    ->label(__('resource.submission.fields.user')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('actions.view')),
                Tables\Actions\EditAction::make()
                    ->label(__('resource.submission.actions.edit'))
                    ->icon('heroicon-o-pencil')
                    ->tooltip(__('resource.submission.actions.edit_tooltip')),
    
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
            Section::make(__('resource.submission.sections.basic_information'))
                ->schema([
                    TextEntry::make('submissionType.name')
                        ->label(__('resource.submission.fields.submission_type')),

                    TextEntry::make('title')
                        ->label(__('resource.submission.fields.title'))
                        ->columnSpanFull(),

                    TextEntry::make('status')
                        ->label(__('resource.submission.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __("resource.submission.status.{$state}"))
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
                        ->label(__('resource.submission.fields.reviewer_notes'))
                        ->markdown()
                        ->columnSpanFull()
                        ->visible(fn($record) => !empty($record->reviewer_notes) && in_array($record->status, ['revision_needed', 'rejected'])),
                    
                    TextEntry::make('currentStage.name')
                        ->label(__('resource.submission.fields.current_stage'))
                        ->visible(fn($record) => !empty($record->current_stage_id)),

                    TextEntry::make('certificate')
                        ->label(__('resource.submission.fields.certificate'))
                        ->visible(fn($record) => !empty($record->certificate)),

                    TextEntry::make('user.fullname')
                        ->label(__('resource.submission.fields.submitted_by')),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label(__('resource.submission.fields.submission_date')),

                    TextEntry::make('updated_at')
                        ->dateTime()
                        ->label(__('resource.general.updated_at'))
                        ->visible(fn($record) => $record->updated_at->ne($record->created_at)),
                ])
                ->columns(2)
                ->collapsible(false),

            // Type-specific section
            Section::make(__('resource.submission.sections.type_details'))
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
                ->label(__('resource.patent.fields.application_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'simple_patent' => __('resource.patent.application_types.simple_patent'),
                    'patent' => __('resource.patent.application_types.patent'),
                    default => $state,
                }),

            TextEntry::make('patentDetail.patent_title')
                ->label(__('resource.patent.fields.patent_title'))
                ->columnSpanFull(),

            TextEntry::make('patentDetail.patent_description')
                ->label(__('resource.patent.fields.patent_description'))
                ->columnSpanFull(),

            TextEntry::make('patentDetail.from_grant_research')
                ->label(__('resource.patent.fields.from_grant_research'))
                ->formatStateUsing(fn($state) => $state ? __('resource.general.yes') : __('resource.general.no')),

            TextEntry::make('patentDetail.self_funded')
                ->label(__('resource.patent.fields.self_funded'))
                ->formatStateUsing(fn($state) => $state ? __('resource.general.yes') : __('resource.general.no')),

            TextEntry::make('patentDetail.inventors_name')
                ->label(__('resource.patent.fields.inventors_name')),

            TextEntry::make('patentDetail.media_link')
                ->label(__('resource.patent.fields.media_link'))
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
                ->label(__('resource.brand.fields.brand_name')),

            TextEntry::make('brandDetail.brand_type')
                ->label(__('resource.brand.fields.brand_type')),

            TextEntry::make('brandDetail.brand_description')
                ->label(__('resource.brand.fields.brand_description'))
                ->columnSpanFull(),

            TextEntry::make('brandDetail.inovators_name')
                ->label(__('resource.brand.fields.inovators_name')),

            TextEntry::make('brandDetail.application_type')
                ->label(__('resource.brand.fields.application_type')),

            TextEntry::make('brandDetail.nice_classes')
                ->label(__('resource.brand.fields.nice_classes'))
                ->visible(fn($record) => !empty($record->brandDetail->nice_classes)),

            TextEntry::make('brandDetail.goods_services_search')
                ->label(__('resource.brand.fields.goods_services_search'))
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
                ->label(__('resource.haki.fields.haki_title'))
                ->columnSpanFull(),

            TextEntry::make('hakiDetail.work_type')
                ->label(__('resource.haki.fields.work_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'literary' => __('resource.haki.work_types.literary'),
                    'musical' => __('resource.haki.work_types.musical'),
                    'dramatic' => __('resource.haki.work_types.dramatic'),
                    'artistic' => __('resource.haki.work_types.artistic'),
                    'audiovisual' => __('resource.haki.work_types.audiovisual'),
                    'sound_recording' => __('resource.haki.work_types.sound_recording'),
                    'computer_program' => __('resource.haki.work_types.computer_program'),
                    default => $state,
                }),

            TextEntry::make('hakiDetail.work_description')
                ->label(__('resource.haki.fields.work_description'))
                ->columnSpanFull(),

            TextEntry::make('hakiDetail.inventors_name')
                ->label(__('resource.haki.fields.inventors_name')),

            TextEntry::make('hakiDetail.registration_number')
                ->label(__('resource.haki.fields.registration_number'))
                ->visible(fn($record) => !empty($record->hakiDetail->registration_number)),

            TextEntry::make('hakiDetail.registration_date')
                ->label(__('resource.haki.fields.registration_date'))
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
                ->label(__('resource.industrial_design.fields.design_title'))
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.design_type')
                ->label(__('resource.industrial_design.fields.design_type')),

            TextEntry::make('industrialDesignDetail.design_description')
                ->label(__('resource.industrial_design.fields.design_description'))
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.novelty_statement')
                ->label(__('resource.industrial_design.fields.novelty_statement'))
                ->columnSpanFull(),

            TextEntry::make('industrialDesignDetail.inventors_name')
                ->label(__('resource.industrial_design.fields.inventors_name')),

            TextEntry::make('industrialDesignDetail.designer_information')
                ->label(__('resource.industrial_design.fields.designer_information')),

            TextEntry::make('industrialDesignDetail.locarno_class')
                ->label(__('resource.industrial_design.fields.locarno_class'))
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
