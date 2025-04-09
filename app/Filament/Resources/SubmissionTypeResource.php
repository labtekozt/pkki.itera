<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionTypeResource\Pages;
use App\Filament\Resources\SubmissionTypeResource\RelationManagers;
use App\Models\SubmissionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubmissionTypeResource extends Resource
{
    protected static ?string $model = SubmissionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash']),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                Tables\Columns\TextColumn::make('documentRequirements_count')
                    ->label('Requirements')
                    ->counts('documentRequirements')
                    ->sortable(),

                Tables\Columns\TextColumn::make('workflowStages_count')
                    ->label('Stages')
                    ->counts('workflowStages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('requirements')
                    ->label('Manage Requirements')
                    ->icon('heroicon-o-document')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('requirements', ['record' => $record])),

                Tables\Actions\Action::make('stages')
                    ->label('Manage Stages')
                    ->icon('heroicon-o-squares-plus')
                    ->url(fn(SubmissionType $record): string =>
                    static::getUrl('stages', ['record' => $record])),
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
            RelationManagers\DocumentRequirementsRelationManager::class,
            RelationManagers\WorkflowStagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionTypes::route('/'),
            'create' => Pages\CreateSubmissionType::route('/create'),
            'edit' => Pages\EditSubmissionType::route('/{record}/edit'),
            'requirements' => Pages\ManageRequirements::route('/{record}/requirements'),
            'stages' => Pages\ManageStages::route('/{record}/stages'),
            'stage-requirements' => Pages\StageRequirements::route('/{record}/stages/{stageId}/requirements'),
        ];
    }
}
