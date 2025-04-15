<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionReviewResource\Pages;
use App\Models\Document;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowStage;
use App\Notifications\ReviewActionNotification;
use App\Notifications\RevisionRequestedNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SubmissionReviewResource extends Resource
{
    protected static ?string $model = Submission::class;
    
    protected static ?string $slug = 'reviews';

    
    protected static ?string $navigationGroup = 'Review Management';
    
    protected static ?string $navigationLabel = 'My Review Tasks';
    
    protected static ?int $navigationSort = 10;
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['reviewer', 'admin', 'super_admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Empty form as we'll create a custom review page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('submissionType.name')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label('Current Stage')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'revision_needed',
                        'primary' => 'in_review',
                        'secondary' => 'draft',
                        'info' => 'submitted',
                    ]),
                Tables\Columns\TextColumn::make('user.fullname')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'in_review' => 'In Review',
                        'revision_needed' => 'Revision Needed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('submission_type_id')
                    ->relationship('submissionType', 'name')
                    ->label('Submission Type'),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->url(fn (Submission $record) => static::getUrl('review', ['record' => $record]))
                    ->color('primary'),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListSubmissionReviews::route('/'),
            'review' => Pages\ReviewSubmission::route('/{record}/review'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
            
        // If user is not an admin, only show submissions assigned to them
        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            $userId = auth()->id();
            
            $query->whereHas('workflowAssignments', function (Builder $q) use ($userId) {
                $q->where('reviewer_id', $userId)
                  ->whereNull('completed_at');
            });
        }
            
        return $query;
    }
}