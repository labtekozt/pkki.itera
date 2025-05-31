<?php

namespace App\Filament\Widgets;

use App\Models\UserFeedback;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserDemographicsChartWidget extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    
    public function getHeading(): string
    {
        return __('resource.widgets.user_demographics');
    }

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
                    'label' => __('resource.widgets.age_groups'),
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
                    'text' => __('resource.widgets.age_distribution_title'),
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function formatAgeGroup(string $ageGroup): string
    {
        return match($ageGroup) {
            'under_30' => __('resource.widgets.age_groups.under_30'),
            '30_40' => __('resource.widgets.age_groups.30_40'),
            '40_50' => __('resource.widgets.age_groups.40_50'),
            '50_60' => __('resource.widgets.age_groups.50_60'),
            '60_70' => __('resource.widgets.age_groups.60_70'),
            'over_70' => __('resource.widgets.age_groups.over_70'),
            default => ucfirst($ageGroup),
        };
    }

    private function formatTechComfort(string $techComfort): string
    {
        return match($techComfort) {
            'expert' => __('resource.widgets.tech_comfort.expert'),
            'advanced' => __('resource.widgets.tech_comfort.advanced'),
            'intermediate' => __('resource.widgets.tech_comfort.intermediate'),
            'beginner' => __('resource.widgets.tech_comfort.beginner'),
            'very_comfortable' => __('resource.widgets.tech_comfort.very_comfortable'),
            'comfortable' => __('resource.widgets.tech_comfort.comfortable'),
            'not_comfortable' => __('resource.widgets.tech_comfort.not_comfortable'),
            'need_help' => __('resource.widgets.tech_comfort.need_help'),
            default => ucfirst($techComfort),
        };
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_user_feedback');
    }
}
