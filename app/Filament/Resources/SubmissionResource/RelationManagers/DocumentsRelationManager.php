<?php

namespace App\Filament\Resources\SubmissionResource\RelationManagers;

use App\Models\DocumentRequirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\IconPosition;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissionDocuments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('requirement_id')
                    ->relationship('requirement', 'name', function (Builder $query) {
                        $submissionTypeId = $this->ownerRecord->submission_type_id;
                        if ($submissionTypeId) {
                            $query->where('submission_type_id', $submissionTypeId);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                
                Forms\Components\FileUpload::make('document')
                    ->disk('public')
                    ->directory('submission-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->hint('Max size: 10MB. Allowed types: PDF, Images, Word docs')
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revision_needed' => 'Revision Needed',
                        'replaced' => 'Replaced',
                        'final' => 'Final',
                    ])
                    ->default('pending')
                    ->required(),
                
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document.title')
            ->columns([
                Tables\Columns\TextColumn::make('requirement.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('document.title')
                    ->searchable()
                    ->url(fn ($record) => route('filament.admin.documents.download', $record->document_id))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-document')
                    ->iconPosition(IconPosition::After)
                    ->tooltip('Click to download'),
                
                Tables\Columns\ImageColumn::make('document.preview_url')
                    ->label('Preview')
                    ->visibility(fn ($record) => 
                        $record->document && 
                        str_contains($record->document->mimetype ?? '', 'image/')
                    )
                    ->square()
                    ->extraImgAttributes(['class' => 'object-contain'])
                    ->size(60),
                
                Tables\Columns\TextColumn::make('document.mimetype')
                    ->label('File Type')
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('document.human_size')
                    ->label('Size'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revision_needed' => 'warning',
                        'replaced' => 'info',
                        'final' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revision_needed' => 'Revision Needed',
                        'replaced' => 'Replaced',
                        'final' => 'Final',
                    ]),
                
                Tables\Filters\SelectFilter::make('requirement_id')
                    ->relationship('requirement', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Document Type'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View File')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.documents.view', $record->document_id))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->document && in_array($record->document->mimetype, [
                        'application/pdf',
                        'image/jpeg', 
                        'image/png', 
                        'image/gif', 
                        'image/svg+xml',
                        'text/plain',
                        'text/html',
                    ])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('filament.admin.documents.download', $record->document_id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
