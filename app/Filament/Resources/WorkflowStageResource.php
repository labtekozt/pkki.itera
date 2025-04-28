<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowStageResource\Pages;
use App\Models\WorkflowStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkflowStageResource extends Resource
{
    protected static ?string $model = WorkflowStage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationGroup = 'Workflow Management';
    
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationLabel(): string
    {
        return 'Workflow Stages';
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: WorkflowStage::class, column: 'code', ignoreRecord: true),
                
                Forms\Components\TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(1),
                
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpan(2),
                
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submissionType.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
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
                Tables\Filters\SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->searchable()
                    ->label('Submission Type'),
                
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('manage_requirements')
                    ->label('Manage Requirements')
                    ->icon('heroicon-o-document-check')
                    ->url(fn (WorkflowStage $record): string => route('filament.admin.resources.workflow-stages.manage-requirements', $record)),
                
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
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowStages::route('/'),
            'create' => Pages\CreateWorkflowStage::route('/create'),
            'edit' => Pages\EditWorkflowStage::route('/{record}/edit'),
            'manage-requirements' => Pages\ManageStageRequirements::route('/{record}/manage-requirements'),
            'manage-stages' => Pages\ManageStages::route('/submission-type/{record}/manage-stages'),
        ];
    }
}