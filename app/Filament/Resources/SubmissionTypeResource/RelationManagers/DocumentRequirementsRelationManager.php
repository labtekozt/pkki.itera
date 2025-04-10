<?php

namespace App\Filament\Resources\SubmissionTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DocumentRequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentRequirements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Requirement Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state || $this->mountedActionName === 'edit') return;
                                $set('code', Str::slug($state, '_'));
                            }),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this requirement'),

                        Forms\Components\Toggle::make('required')
                            ->label('Is Required')
                            ->helperText('Is this document mandatory for all submissions?')
                            ->default(true),

                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(fn($livewire) => $livewire->ownerRecord->documentRequirements()->count() + 1),

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

                Forms\Components\Section::make('Example Document')
                    ->schema([
                        Forms\Components\FileUpload::make('example_document')
                            ->label('Example Document')
                            ->disk('public')
                            ->directory('document-requirements/examples')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'text/plain'
                            ])
                            ->helperText('Upload an example document that users can reference')
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => Str::limit($record->description, 40)),

                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('standard_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('required')
                    ->boolean(),

                Tables\Columns\TextColumn::make('stage_count')
                    ->label('Used in Stages')
                    ->getStateUsing(function ($record) {
                        return $record->workflowStages()->count();
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('required')
                    ->label('Required Status')
                    ->placeholder('All Requirements')
                    ->trueLabel('Required Documents')
                    ->falseLabel('Optional Documents'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) =>
                    route('filament.admin.resources.submissions.index', [
                        'tableFilters[document_requirement]' => $record->id
                    ]))
                    ->visible(fn() => auth()->user()->can('viewAny', \App\Models\Submission::class)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_required')
                        ->label('Toggle Required Status')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['required' => !$record->required]);
                            }
                            $this->notify('success', 'Requirements updated successfully');
                        }),
                ]),
            ]);
    }
}
