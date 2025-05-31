<?php

namespace App\Filament\Widgets;

use App\Models\UserFeedback;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class UserFeedbackStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static bool $isLazy = false;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalFeedback = UserFeedback::count();
        $avgRating = UserFeedback::avg('overall_rating');
        $criticalFeedback = UserFeedback::critical()->count();
        $unprocessedFeedback = UserFeedback::where('processed', false)->count();

        // Calculate trends (compare with previous 30 days)
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo = now()->subDays(60);
        
        $recentFeedback = UserFeedback::where('created_at', '>=', $thirtyDaysAgo)->count();
        $previousFeedback = UserFeedback::whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();
        $feedbackTrend = $previousFeedback > 0 ? (($recentFeedback - $previousFeedback) / $previousFeedback) * 100 : 0;

        $recentAvgRating = UserFeedback::where('created_at', '>=', $thirtyDaysAgo)->avg('overall_rating');
        $previousAvgRating = UserFeedback::whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->avg('overall_rating');
        $ratingTrend = $previousAvgRating > 0 ? (($recentAvgRating - $previousAvgRating) / $previousAvgRating) * 100 : 0;

        return [
            Stat::make('Total Feedback', $totalFeedback)
                ->description($recentFeedback . ' in last 30 days')
                ->descriptionIcon($feedbackTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getFeedbackChart())
                ->color($feedbackTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Average Rating', number_format($avgRating, 1) . '/5')
                ->description(number_format($recentAvgRating, 1) . '/5 recent average')
                ->descriptionIcon($ratingTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getRatingChart())
                ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger')),

            Stat::make('Critical Issues', $criticalFeedback)
                ->description('Low ratings (≤2 stars) or accessibility issues')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($criticalFeedback > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.user-feedback.index', ['activeTab' => 'critical'])),

            Stat::make('Needs Review', $unprocessedFeedback)
                ->description('Unprocessed feedback requiring attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unprocessedFeedback > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.user-feedback.index', ['activeTab' => 'unprocessed'])),
        ];
    }

    private function getFeedbackChart(): array
    {
        // Get daily feedback count for the last 7 days
        $data = UserFeedback::select(DB::raw('DATE(created_at) as date, COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // Fill missing days with 0
        while (count($data) < 7) {
            array_unshift($data, 0);
        }

        return array_slice($data, -7);
    }

    private function getRatingChart(): array
    {
        // Get daily average rating for the last 7 days
        $data = UserFeedback::select(DB::raw('DATE(created_at) as date, AVG(overall_rating) as avg_rating'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_rating')
            ->map(fn($rating) => round($rating * 20, 1)) // Convert to percentage for chart
            ->toArray();

        // Fill missing days with 0
        while (count($data) < 7) {
            array_unshift($data, 0);
        }

        return array_slice($data, -7);
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_user_feedback');
    }
}
