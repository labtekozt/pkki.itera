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
                ->form([
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
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('required')
                        ->label('Required Document')
                        ->default(true),

                    Forms\Components\TextInput::make('order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(fn($livewire) => DocumentRequirement::where('submission_type_id', $livewire->ownerRecord->id)->count() + 1),
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
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable(),

                Tables\Columns\IconColumn::make('required')
                    ->boolean(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
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
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('required')
                            ->label('Required Document')
                            ->default(true),

                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric(),
                    ])
                    ->mutateFormDataUsing(function (array $data, $record) {
                        $data['submission_type_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-document-text')
                    ->tooltip('View submissions using this requirement')
                    ->url(fn(DocumentRequirement $record): string =>
                    route('filament.admin.resources.submissions.index', [
                        'tableFilters[document_requirement]' => $record->id
                    ]))
                    ->visible(fn() => auth()->user()->can('viewAny', \App\Models\Submission::class)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
