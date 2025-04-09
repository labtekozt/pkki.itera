<?php

namespace App\Filament\Resources\SubmissionTypeResource\Pages;

use App\Filament\Resources\SubmissionTypeResource;
use App\Models\DocumentRequirement;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ManageRequirements extends ManageRelatedRecords
{
    protected static string $resource = SubmissionTypeResource::class;

    protected static string $relationship = 'documentRequirements';

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $title = 'Manage Document Requirements';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Add New Document Requirement')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Section::make('Requirement Details')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) return;
                                    $set('code', Str::slug($state, '_'));
                                }),

                            Forms\Components\TextInput::make('code')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->helperText('Unique identifier for this requirement'),

                            Forms\Components\Toggle::make('required')
                                ->label('Required Document')
                                ->helperText('Is this document mandatory for all submissions?')
                                ->default(true),

                            Forms\Components\TextInput::make('order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(function () {
                                    return DocumentRequirement::where(
                                        'submission_type_id', 
                                        $this->getOwnerRecord()->id
                                    )->count() + 1;
                                }),

                            Forms\Components\Textarea::make('description')
                                ->rows(3)
                                ->helperText('Provide details about what this document should contain')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Validation Rules')
                        ->schema([
                            Forms\Components\Select::make('allowed_file_types')
                                ->label('Allowed File Types')
                                ->multiple()
                                ->options([
                                    'pdf' => 'PDF Documents',
                                    'doc' => 'Word Documents (DOC)',
                                    'docx' => 'Word Documents (DOCX)',
                                    'jpg' => 'JPEG Images',
                                    'png' => 'PNG Images',
                                    'txt' => 'Text Files',
                                ])
                                ->placeholder('All file types allowed')
                                ->helperText('Leave empty to allow all file types')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('max_file_size')
                                ->label('Maximum File Size (MB)')
                                ->numeric()
                                ->placeholder('5')
                                ->helperText('Leave empty for system default'),
                        ])
                        ->collapsible()
                        ->collapsed(),
                ])
                ->mutateFormDataUsing(function (array $data) {
                    $data['submission_type_id'] = $this->getOwnerRecord()->id;
                    return $data;
                })
                ->successRedirectUrl(fn() => $this->getResource()::getUrl('requirements', ['record' => $this->getOwnerRecord()])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => Str::limit($record->description, 40)),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('required')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('stage_count')
                    ->label('Used in Stages')
                    ->state(function ($record) {
                        return $record->workflowStages()->count();
                    })
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('submissionDocuments_count')
                    ->label('Documents Submitted')
                    ->state(function ($record) {
                        return $record->submissionDocuments()->count();
                    })
                    ->color('warning')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('required')
                    ->label('Required Status')
                    ->placeholder('All Requirements')
                    ->trueLabel('Required Documents')
                    ->falseLabel('Optional Documents'),

                Tables\Filters\Filter::make('used_in_stages')
                    ->label('Used in Workflow Stages')
                    ->query(function (Builder $query) {
                        return $query->whereHas('workflowStages');
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('has_submissions')
                    ->label('Has Submissions')
                    ->query(function (Builder $query) {
                        return $query->whereHas('submissionDocuments');
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Section::make('Requirement Details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) return;
                                        $set('code', Str::slug($state, '_'));
                                    }),

                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->disabled()
                                    ->helperText('Code cannot be changed after creation'),

                                Forms\Components\Toggle::make('required')
                                    ->label('Required Document')
                                    ->helperText('Is this document mandatory for all submissions?'),

                                Forms\Components\TextInput::make('order')
                                    ->label('Display Order')
                                    ->numeric(),

                                Forms\Components\Textarea::make('description')
                                    ->rows(3)
                                    ->helperText('Provide details about what this document should contain')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Validation Rules')
                            ->schema([
                                Forms\Components\Select::make('allowed_file_types')
                                    ->label('Allowed File Types')
                                    ->multiple()
                                    ->options([
                                        'pdf' => 'PDF Documents',
                                        'doc' => 'Word Documents (DOC)',
                                        'docx' => 'Word Documents (DOCX)',
                                        'jpg' => 'JPEG Images',
                                        'png' => 'PNG Images',
                                        'txt' => 'Text Files',
                                    ])
                                    ->placeholder('All file types allowed')
                                    ->helperText('Leave empty to allow all file types')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('max_file_size')
                                    ->label('Maximum File Size (MB)')
                                    ->numeric()
                                    ->placeholder('5')
                                    ->helperText('Leave empty for system default'),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->mutateFormDataUsing(function (array $data, $record) {
                        $data['submission_type_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this requirement? This may affect submissions that use this requirement.'),

                Tables\Actions\Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-document-text')
                    ->tooltip('View submissions using this requirement')
                    ->url(fn(DocumentRequirement $record): string =>
                    route('filament.admin.resources.submissions.index', [
                        'tableFilters[document_requirement]' => $record->id
                    ]))
                    ->visible(fn() => auth()->user()->can('viewAny', \App\Models\Submission::class)),

                Tables\Actions\Action::make('toggle_required')
                    ->label(fn($record) => $record->required ? 'Mark as Optional' : 'Mark as Required')
                    ->icon(fn($record) => $record->required ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->required ? 'danger' : 'success')
                    ->action(function (DocumentRequirement $record) {
                        $record->update(['required' => !$record->required]);
                        $this->notify('success', 'Requirement updated successfully');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these requirements? This may affect submissions that use them.'),

                    Tables\Actions\BulkAction::make('toggle_required')
                        ->label('Toggle Required Status')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['required' => !$record->required]);
                            }
                            $this->notify('success', 'Requirements updated successfully');
                        }),

                    Tables\Actions\BulkAction::make('mark_required')
                        ->label('Mark as Required')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['required' => true]);
                            }
                            $this->notify('success', 'Requirements marked as required');
                        }),

                    Tables\Actions\BulkAction::make('mark_optional')
                        ->label('Mark as Optional')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['required' => false]);
                            }
                            $this->notify('success', 'Requirements marked as optional');
                        }),
                ]),
            ]);
    }
}
