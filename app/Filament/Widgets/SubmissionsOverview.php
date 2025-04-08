<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use App\Models\SubmissionType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubmissionsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $stats = [];
        
        // Get total submissions count by type
        $submissionTypes = SubmissionType::all();
        
        foreach ($submissionTypes as $type) {
            $count = Submission::where('submission_type_id', $type->id)->count();
            $inProgress = Submission::where('submission_type_id', $type->id)
                ->whereIn('status', ['in_review', 'submitted'])
                ->count();
            
            $stats[] = Stat::make("{$type->name} Submissions", $count)
                ->description("{$inProgress} in progress")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info');
        }
        
        // Add overall statistics
        $stats[] = Stat::make('Total Submissions', Submission::count())
            ->description(Submission::where('created_at', '>=', now()->subDays(30))->count() . ' new in 30 days')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->chart([15, 30, 20, 45, 30, 80, 60])
            ->color('success');
            
        $stats[] = Stat::make('Approved Submissions', Submission::where('status', 'approved')->count())
            ->description('Success rate: ' . round((Submission::where('status', 'approved')->count() / max(1, Submission::count())) * 100, 1) . '%')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->chart([5, 10, 15, 20, 25, 30, 35])
            ->color('success');
            
        return $stats;
    }
}
