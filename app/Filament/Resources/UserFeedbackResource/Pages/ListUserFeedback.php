<?php

namespace App\Filament\Resources\UserFeedbackResource\Pages;

use App\Filament\Resources\UserFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserFeedback extends ListRecords
{
    protected static string $resource = UserFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analytics')
                ->label('View Analytics')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->action(function () {
                    $this->notify('info', 'Analytics dashboard coming soon!');
                }),
                
            Actions\Action::make('export')
                ->label('Export Feedback')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $this->notify('success', 'Feedback data exported successfully!');
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Feedback')
                ->badge($this->getModel()::count()),
                
            'critical' => Tab::make('Critical Issues')
                ->modifyQueryUsing(fn (Builder $query) => $query->critical())
                ->badge($this->getModel()::critical()->count())
                ->badgeColor('danger'),
                
            'unprocessed' => Tab::make('Needs Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->unprocessed())
                ->badge($this->getModel()::unprocessed()->count())
                ->badgeColor('warning'),
                
            'high_rating' => Tab::make('Positive (4-5 ⭐)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', '>=', 4))
                ->badge($this->getModel()::where('rating', '>=', 4)->count())
                ->badgeColor('success'),
                
            'low_rating' => Tab::make('Critical (1-2 ⭐)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', '<=', 2))
                ->badge($this->getModel()::where('rating', '<=', 2)->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getTablePollingInterval(): ?string
    {
        return '10s'; // Refresh every 10 seconds for real-time feedback
    }
}
