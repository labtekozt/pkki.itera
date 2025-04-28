<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Filament\Widgets\SubmissionProgressWidget;
use App\Services\WorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class ProcessSubmission extends Page
{
    protected static string $resource = SubmissionResource::class;
    protected static string $view = 'filament.resources.submission-resource.pages.process-submission';

    public ?string $action = null;
    public ?string $comment = null;
    public ?string $rejectReason = null;
    public ?string $targetStageId = null;

    public function mount(string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $this->authorizeAccess();
    }
    
    /**
     * Resolve the record for the page using the provided key.
     */
    protected function resolveRecord(string $key): Model
    {
        $model = static::getResource()::getModel();
        
        $record = $model::find($key);
        
        if ($record === null) {
            abort(404);
        }
        
        return $record;
    }
    
    protected function authorizeAccess(): void
    {
        static::authorizeResourceAccess();
        
        abort_unless(
            Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'),
            403
        );
        
        abort_if(
            in_array($this->record->status, ['draft', 'completed']),
            404
        );
    }
    
    public function getWorkflowStatus()
    {
        return match($this->record->status) {
            'draft' => ['label' => 'Draft', 'color' => 'gray'],
            'submitted' => ['label' => 'Submitted', 'color' => 'info'],
            'in_review' => ['label' => 'In Review', 'color' => 'primary'],
            'revision_needed' => ['label' => 'Revision Needed', 'color' => 'warning'],
            'approved' => ['label' => 'Approved', 'color' => 'success'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
            'completed' => ['label' => 'Completed', 'color' => 'success'],
            default => ['label' => ucfirst($this->record->status), 'color' => 'gray'],
        };
    }
    
    public function getAvailableActions()
    {
        $workflowService = app(WorkflowService::class);
        return $workflowService->getAvailableActions($this->record);
    }
    
    public function getNextStages()
    {
        if (!$this->record->currentStage) {
            return [];
        }
        
        return $this->record->submissionType->workflowStages()
            ->where('order', '>', $this->record->currentStage->order)
            ->where('is_active', true)
            ->orderBy('order')
            ->pluck('name', 'id')
            ->toArray();
    }
    
    public function getPreviousStages()
    {
        if (!$this->record->currentStage) {
            return [];
        }
        
        return $this->record->submissionType->workflowStages()
            ->where('order', '<', $this->record->currentStage->order)
            ->where('is_active', true)
            ->orderBy('order', 'desc')
            ->pluck('name', 'id')
            ->toArray();
    }
    
    public function getRejectReasons()
    {
        return [
            'incomplete' => 'Incomplete Documentation',
            'ineligible' => 'Does Not Meet Eligibility Requirements',
            'duplicate' => 'Duplicate Submission',
            'inappropriate' => 'Inappropriate Content',
            'technical' => 'Technical Issues',
            'other' => 'Other (Please Specify)',
        ];
    }
    
    public function processAction()
    {
        $this->validate([
            'action' => 'required|string',
            'comment' => 'required|string',
        ]);
        
        try {
            $workflowService = app(WorkflowService::class);
            $options = [
                'comment' => $this->comment,
                'processor' => Auth::user(),
            ];
            
            if ($this->targetStageId) {
                $options['target_stage_id'] = $this->targetStageId;
            }
            
            if ($this->rejectReason) {
                $options['reason'] = $this->rejectReason;
            }
            
            $submission = $workflowService->processAction($this->record, $this->action, $options);
            
            Notification::make()
                ->title('Workflow action processed successfully')
                ->success()
                ->send();
                
            // Redirect to view page after processing
            return redirect()->to(
                SubmissionResource::getUrl('view', ['record' => $this->record])
            );
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error processing workflow action')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            SubmissionProgressWidget::make([
                'submission' => $this->record,
            ]),
        ];
    }
}
