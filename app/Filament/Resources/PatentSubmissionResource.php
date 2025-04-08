<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatentSubmissionResource\Pages;
use App\Models\PatentDetail;
use App\Models\Submission;
use App\Models\SubmissionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PatentSubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'Patents';
    
    protected static ?string $navigationGroup = 'Intellectual Property';
    
    protected static ?int $navigationSort = 2;
    
    // Build a query that only returns Patent submissions
    public static function getEloquentQuery(): Builder
    {
        // Get the Patent submission type
        $patentType = SubmissionType::where('slug', 'paten')->first();
        
        return parent::getEloquentQuery()
            ->where('submission_type_id', $patentType?->id)
            ->with('patentDetail'); // Eager load the patent details
    }

    public static function form(Form $form): Form
    {
        // Here, reuse most of the form from the main SubmissionResource,
        // but only include the Patent-specific fields
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        
                        Forms\Components\Hidden::make('submission_type_id')
                            ->default(function () {
                                $patentType = SubmissionType::where('slug', 'paten')->first();
                                return $patentType?->id;
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
                            ->relationship('currentStage', 'name', function (Builder $query) {
                                $patentType = SubmissionType::where('slug', 'paten')->first();
                                if ($patentType) {
                                    $query->where('submission_type_id', $patentType->id)
                                        ->orderBy('order');
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->label('Current Stage'),
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
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('patentDetail.patent_type')
                    ->label('Patent Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'utility' => 'Utility Patent',
                        'design' => 'Design Patent',
                        'plant' => 'Plant Patent',
                        'process' => 'Process Patent',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('user.fullname')
                    ->label('Submitter')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
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
                    
                Tables\Filters\SelectFilter::make('patent_type')
                    ->options([
                        'utility' => 'Utility Patent',
                        'design' => 'Design Patent',
                        'plant' => 'Plant Patent',
                        'process' => 'Process Patent',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        
                        return $query->whereHas('patentDetail', function (Builder $query) use ($data) {
                            $query->where('patent_type', $data['value']);
                        });
                    }),
                    
                Tables\Filters\SelectFilter::make('user_id')
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
            'index' => Pages\ListPatentSubmissions::route('/'),
            'create' => Pages\CreatePatentSubmission::route('/create'),
            'view' => Pages\ViewPatentSubmission::route('/{record}'),
            'edit' => Pages\EditPatentSubmission::route('/{record}/edit'),
        ];
    }    
}
