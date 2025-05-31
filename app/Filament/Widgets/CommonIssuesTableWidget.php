<?php

namespace App\Filament\Widgets;

use App\Models\UserFeedback;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CommonIssuesTableWidget extends BaseWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    public function getHeading(): string
    {
        return __('resource.widgets.common_usability_issues');
    }

    protected function getTableQuery(): Builder
    {
        // We'll override getTableRecords instead since this is aggregated data
        return UserFeedback::query()->whereRaw('1 = 0'); // Empty query
    }

    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection
    {
        $feedbacks = UserFeedback::whereNotNull('difficulty_areas')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $issueCount = [];
        foreach ($feedbacks as $feedback) {
            $areas = $feedback->difficulty_areas ?? [];
            foreach ($areas as $area) {
                $issueCount[$area] = ($issueCount[$area] ?? 0) + 1;
            }
        }

        // Sort by frequency
        arsort($issueCount);

        $issueLabels = [
            'navigation' => __('resource.widgets.issues.navigation'),
            'forms' => __('resource.widgets.issues.forms'),
            'upload' => __('resource.widgets.issues.upload'),
            'understanding' => __('resource.widgets.issues.understanding'),
            'text_size' => __('resource.widgets.issues.text_size'),
            'buttons' => __('resource.widgets.issues.buttons'),
            'reading' => __('resource.widgets.issues.reading'),
            'layout' => __('resource.widgets.issues.layout'),
            'speed' => __('resource.widgets.issues.speed'),
            'mobile' => __('resource.widgets.issues.mobile'),
        ];

        $result = [];
        $total = array_sum($issueCount);
        $rank = 1;

        foreach ($issueCount as $issue => $count) {
            $result[] = (object)[
                'rank' => $rank++,
                'issue' => $issue,
                'label' => $issueLabels[$issue] ?? ucfirst(str_replace('_', ' ', $issue)),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'severity' => $this->getSeverity($count, $total),
                'reports_last_week' => $this->getRecentReports($issue),
            ];
        }

        return collect(array_slice($result, 0, 10)); // Top 10 issues
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('rank')
                ->label(__('resource.widgets.rank'))
                ->alignCenter()
                ->sortable(),
                
            Tables\Columns\TextColumn::make('label')
                ->label(__('resource.widgets.issue'))
                ->searchable()
                ->weight('semibold'),
                
            Tables\Columns\TextColumn::make('count')
                ->label(__('resource.widgets.reports'))
                ->alignCenter()
                ->badge()
                ->color(fn ($record) => match($record->severity) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    'low' => 'success',
                    default => 'gray',
                }),
                
            Tables\Columns\TextColumn::make('percentage')
                ->label(__('resource.widgets.percentage_users'))
                ->alignCenter()
                ->formatStateUsing(fn ($state) => $state . '%'),
                
            Tables\Columns\TextColumn::make('severity')
                ->label(__('resource.widgets.severity'))
                ->alignCenter()
                ->badge()
                ->color(fn ($state) => match($state) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    'low' => 'success',
                    default => 'gray',
                }),
                
            Tables\Columns\TextColumn::make('reports_last_week')
                ->label(__('resource.widgets.recent_reports'))
                ->alignCenter()
                ->description(__('resource.widgets.last_7_days')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view_details')
                ->label(__('resource.widgets.view_details'))
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('filament.admin.resources.user-feedback.index', [
                    'tableFilters' => [
                        'difficulty_areas' => ['value' => $record->issue]
                    ]
                ]))
                ->openUrlInNewTab(),
        ];
    }

    private function getSeverity(int $count, int $total): string
    {
        if ($total === 0) return 'low';
        
        $percentage = ($count / $total) * 100;
        
        if ($percentage >= 20) return 'high';
        if ($percentage >= 10) return 'medium';
        return 'low';
    }

    private function getRecentReports(string $issue): int
    {
        return UserFeedback::where('created_at', '>=', now()->subDays(7))
            ->whereJsonContains('difficulty_areas', $issue)
            ->count();
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_user_feedback');
    }
}
