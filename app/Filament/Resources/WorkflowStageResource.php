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
    
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.workflow_management');
    }
    
    public static function getNavigationLabel(): string
    {
        return __('resource.workflow_stages');
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
                    ->label(__('resource.submission_type'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('name')
                    ->label(__('resource.stage_name'))
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('code')
                    ->label(__('resource.stage_code'))
                    ->required()
                    ->maxLength(255)
                    ->unique(table: WorkflowStage::class, column: 'code', ignoreRecord: true),
                
                Forms\Components\TextInput::make('order')
                    ->label(__('resource.stage_order'))
                    ->required()
                    ->numeric()
                    ->default(1),
                
                Forms\Components\Textarea::make('description')
                    ->label(__('resource.stage_description'))
                    ->maxLength(65535)
                    ->columnSpan(2),
                
                Forms\Components\Toggle::make('is_active')
                    ->label(__('resource.is_active'))
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submissionType.name')
                    ->label(__('resource.submission_type'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.stage_name'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resource.stage_code'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label(__('resource.stage_order'))
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('resource.is_active'))
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resource.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->searchable()
                    ->label(__('resource.submission_type_filter')),
                
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => __('resource.active'),
                        '0' => __('resource.inactive'),
                    ])
                    ->label(__('resource.status_filter')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('resource.edit')),
                
                Tables\Actions\Action::make('manage_requirements')
                    ->label(__('resource.manage_requirements'))
                    ->icon('heroicon-o-document-check')
                    ->url(fn (WorkflowStage $record): string => route('filament.admin.resources.workflow-stages.manage-requirements', $record)),
                
                Tables\Actions\DeleteAction::make()
                    ->label(__('resource.delete')),
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