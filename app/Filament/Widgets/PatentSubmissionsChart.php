<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use App\Models\SubmissionType;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatentSubmissionsChart extends ChartWidget
{
    protected static ?string $heading = null;
    
    protected static ?int $sort = 2;
    
    public function getHeading(): string
    {
        return __('resource.widgets.patent_submissions_chart');
    }
    
    protected function getData(): array
    {
        // Get the Patent submission type
        $patentType = SubmissionType::where('slug', 'paten')->first();
        
        if (!$patentType) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }
        
        // Get the last 12 months as labels
        $labels = collect();
        $totalData = collect();
        $approvedData = collect();
        
        // Generate data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->format('M Y');
            $labels->push($month);
            
            // Count total submissions for this month
            $total = Submission::where('submission_type_id', $patentType->id)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $totalData->push($total);
            
            // Count approved submissions for this month
            $approved = Submission::where('submission_type_id', $patentType->id)
                ->where('status', 'approved')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $approvedData->push($approved);
        }
        
        return [
            'datasets' => [
                [
                    'label' => __('resource.widgets.all_patent_submissions'),
                    'data' => $totalData->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => __('resource.widgets.approved_patents'),
                    'data' => $approvedData->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
