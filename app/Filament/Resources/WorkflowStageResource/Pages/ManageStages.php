<?php

namespace App\Filament\Resources\WorkflowStageResource\Pages;

use App\Filament\Resources\WorkflowStageResource;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageStages extends Page
{
    protected static string $resource = WorkflowStageResource::class;

    protected static string $view = 'filament.resources.workflow-stage-resource.pages.manage-stages';
    
    public ?SubmissionType $record = null;
    
    public function mount(SubmissionType $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Add Workflow Stage')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Stage Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->label('Stage Code')
                            ->required()
                            ->unique(table: WorkflowStage::class, column: 'code')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->required()
                            ->default(function () {
                                // Get the max order and add 1
                                return WorkflowStage::where('submission_type_id', $this->record->id)
                                    ->max('order') + 1;
                            }),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Is this stage currently active in the workflow?'),
                    ])
                    ->columns(3),
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkflowStage::query()
                    ->where('submission_type_id', $this->record->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Stage Name')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Stage Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->label('Stage Code')
                            ->required()
                            ->unique(table: WorkflowStage::class, column: 'code', ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->required(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->modalHeading('Edit Workflow Stage'),
                
                Tables\Actions\Action::make('manage_requirements')
                    ->label('Manage Requirements')
                    ->icon('heroicon-o-document-check')
                    ->url(fn (WorkflowStage $record): string => route('filament.admin.resources.workflow-stages.manage-requirements', $record)),
                
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete Workflow Stage')
                    ->modalDescription('Are you sure you want to delete this workflow stage? This action cannot be undone if the stage is already in use.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order');
    }
    
    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            WorkflowStage::create([
                'id' => Str::uuid()->toString(),
                'submission_type_id' => $this->record->id,
                'name' => $data['name'],
                'code' => $data['code'],
                'order' => $data['order'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            
            Notification::make()
                ->title('Workflow stage added')
                ->success()
                ->send();
                
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error adding workflow stage')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Submission Types')
                ->url(route('filament.admin.resources.submission-types.index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}