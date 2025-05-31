<?php

namespace App\Filament\Widgets;

use App\Models\UserFeedback;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserDemographicsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'User Demographics & Tech Comfort';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        // Get age group distribution
        $ageData = UserFeedback::select('age_group', DB::raw('count(*) as count'))
            ->whereNotNull('age_group')
            ->groupBy('age_group')
            ->get();

        // Get tech comfort distribution
        $techComfortData = UserFeedback::select('tech_comfort_level', DB::raw('count(*) as count'))
            ->whereNotNull('tech_comfort_level')
            ->groupBy('tech_comfort_level')
            ->get();

        $ageLabels = [];
        $ageCounts = [];
        foreach ($ageData as $item) {
            $ageLabels[] = $this->formatAgeGroup($item->age_group);
            $ageCounts[] = $item->count;
        }

        $techLabels = [];
        $techCounts = [];
        foreach ($techComfortData as $item) {
            $techLabels[] = $this->formatTechComfort($item->tech_comfort_level);
            $techCounts[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Age Groups',
                    'data' => $ageCounts,
                    'backgroundColor' => [
                        '#10B981', // Green
                        '#3B82F6', // Blue
                        '#F59E0B', // Amber
                        '#EF4444', // Red
                        '#8B5CF6', // Purple
                        '#06B6D4', // Cyan
                    ],
                ],
            ],
            'labels' => $ageLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Age Distribution of Feedback Users',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function formatAgeGroup(string $ageGroup): string
    {
        return match($ageGroup) {
            'under_30' => 'Under 30',
            '30_40' => '30-40',
            '40_50' => '40-50',
            '50_60' => '50-60',
            '60_70' => '60-70',
            'over_70' => 'Over 70',
            default => ucfirst($ageGroup),
        };
    }

    private function formatTechComfort(string $techComfort): string
    {
        return match($techComfort) {
            'expert' => 'Expert',
            'advanced' => 'Advanced',
            'intermediate' => 'Intermediate',
            'beginner' => 'Beginner',
            'very_comfortable' => 'Very Comfortable',
            'comfortable' => 'Comfortable',
            'not_comfortable' => 'Not Comfortable',
            'need_help' => 'Need Help',
            default => ucfirst($techComfort),
        };
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_user_feedback');
    }
}
