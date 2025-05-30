<?php

namespace App\Filament\Resources\SubmissionReviewResource\Pages;

use App\Filament\Resources\SubmissionReviewResource;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowStage;
use App\Notifications\ReviewActionNotification;
use App\Services\SubmissionDetailsService;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ReviewSubmission extends Page
{
    protected static string $resource = SubmissionReviewResource::class;

    protected static string $view = 'filament.resources.submission-review-resource.pages.review-submission';

    public ?Submission $record = null;

    public array $data = [];
    public $reviewDecision = null;
    public $reviewComments = '';
    public $nextStageId = null;
    public $nextReviewerId = null;
    public $stageRequirementsSatisfied = false;
    public $documentStatuses = [];

    /**
     * Service for generating submission detail components 
     */
    protected SubmissionDetailsService $submissionDetailsService;

    /**
     * Constructor with service dependency injection
     */
    public function __construct(?SubmissionDetailsService $submissionDetailsService = null)
    {
        $this->submissionDetailsService = $submissionDetailsService ?? app(SubmissionDetailsService::class);
    }

    public function mount(Submission $record): void
    {
        $this->record = $record;

        // Check if this submission can be reviewed
        if (!$record->canSubmitReview()) {
            Notification::make()
                ->title('Cannot review submission')
                ->body('This submission is not currently in a reviewable state or you do not have permission to review it.')
                ->warning()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }

        // Check stage requirements satisfaction
        $this->checkStageRequirementsSatisfaction();

        // Initialize document statuses
        $this->initializeDocumentStatuses();
    }

    /**
     * Check if current stage requirements are satisfied
     * At least one document must be approved to proceed to the next stage
     */
    public function checkStageRequirementsSatisfaction(): void
    {
        try {
            if (!$this->record || !$this->record->submissionType || !$this->record->currentStage) {
                $this->stageRequirementsSatisfied = false;
                return;
            }

            // Get stage requirements for the current stage
            $stageRequirements = $this->record->currentStage->stageRequirements()
                ->pluck('document_requirement_id')
                ->toArray();

            if (empty($stageRequirements)) {
                // If no specific requirements defined for this stage, consider it satisfied
                $this->stageRequirementsSatisfied = true;
                return;
            }

            // Check if at least one document for each requirement is approved
            $approvedRequirements = $this->record->submissionDocuments()
                ->whereIn('requirement_id', $stageRequirements)
                ->where('status', 'approved')
                ->pluck('requirement_id')
                ->unique()
                ->toArray();

            // All requirements must have at least one approved document
            $this->stageRequirementsSatisfied = count($approvedRequirements) === count($stageRequirements);
        } catch (\Exception $e) {
            $this->stageRequirementsSatisfied = false;

            Notification::make()
                ->title('Error checking requirements')
                ->body('There was an error checking stage requirements: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Initialize document statuses for the form
     */
    protected function initializeDocumentStatuses(): void
    {
        if (!$this->record) return;

        $documents = $this->record->submissionDocuments()
            ->with(['document', 'requirement'])
            // Prioritize active documents
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($documents as $doc) {
            $this->documentStatuses["document_{$doc->id}"] = $doc->status;
        }
    }

    /**
     * Get all workflow stages for this submission with status indicators
     */
    protected function getWorkflowStages(): array
    {
        if (!$this->record || !$this->record->submissionType) {
            return [];
        }

        $stages = $this->record->submissionType->workflowStages()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $currentStageId = $this->record->current_stage_id;
        $currentStageOrder = null;

        if ($currentStageId) {
            $currentStage = $stages->firstWhere('id', $currentStageId);
            $currentStageOrder = $currentStage->order ?? null;
        }

        $result = [];
        foreach ($stages as $stage) {
            // Determine the status of each stage
            $status = 'upcoming';
            if ($stage->id === $currentStageId) {
                $status = 'current';
            } elseif ($currentStageOrder && $stage->order < $currentStageOrder) {
                $status = 'completed';
            }

            // Get the latest tracking history for this stage
            $latestHistory = $this->record->trackingHistory()
                ->where('stage_id', $stage->id)
                ->latest('created_at')
                ->first();

            $result[] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'order' => $stage->order,
                'description' => $stage->description,
                'status' => $status,
                'date' => $latestHistory ? $latestHistory->created_at->format('M d, Y') : null,
            ];
        }

        return $result;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Placeholder::make('submission_status')
                            ->content(function () {
                                $status = $this->record->status;
                                $statusColor = match ($status) {
                                    'draft' => 'gray',
                                    'submitted' => 'info',
                                    'in_review' => 'warning',
                                    'revision_needed' => 'danger',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'completed' => 'success',
                                    'cancelled' => 'gray',
                                    default => 'gray',
                                };

                                $content = "<div class='p-4 bg-{$statusColor}-50 border border-{$statusColor}-200 rounded-lg shadow-sm mb-4'>";
                                $content .= "<div class='flex items-center mb-2'>";

                                // Status icon
                                $statusIcon = match ($status) {
                                    'draft' => '<svg class="h-5 w-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" clip-rule="evenodd"></path></svg>',
                                    'submitted' => '<svg class="h-5 w-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"></path></svg>',
                                    'in_review' => '<svg class="h-5 w-5 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z" clip-rule="evenodd"></path><path d="M10 4a1 1 0 011 1v4.586l2.707 2.707a1 1 0 01-1.414 1.414l-3-3A1 1 0 019 10V5a1 1 0 011-1z" clip-rule="evenodd"></path></svg>',
                                    'revision_needed' => '<svg class="h-5 w-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                                    'approved' => '<svg class="h-5 w-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                    'rejected' => '<svg class="h-5 w-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                                    'completed' => '<svg class="h-5 w-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                    'cancelled' => '<svg class="h-5 w-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                                    default => '<svg class="h-5 w-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>',
                                };

                                $content .= $statusIcon;
                                $content .= "<h3 class='text-lg font-medium text-{$statusColor}-700'>Current Status: " . ucfirst(str_replace('_', ' ', $status)) . "</h3>";
                                $content .= "</div>";

                                if ($this->record->currentStage) {
                                    $content .= "<p class='ml-7 text-{$statusColor}-600'>Current Stage: {$this->record->currentStage->name}</p>";
                                }

                                $content .= "</div>";

                                // Add workflow timeline visualization
                                $stages = $this->getWorkflowStages();
                                if (count($stages) > 0) {
                                    $content .= "<div class='mt-6 mb-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border border-gray-100 dark:border-gray-700'>";
                                    $content .= "<h4 class='text-sm font-medium text-gray-700 dark:text-gray-300 mb-4'>Workflow Progress</h4>";

                                    // Timeline container with progress bar
                                    $content .= "<div class='relative'>";

                                    // Calculate progress percentage
                                    $totalStages = count($stages);
                                    $completedStages = 0;
                                    $currentStageFound = false;
                                    $currentStageIndex = 0;

                                    foreach ($stages as $index => $stage) {
                                        if ($stage['status'] === 'completed') {
                                            $completedStages++;
                                        }
                                        if ($stage['status'] === 'current') {
                                            $currentStageFound = true;
                                            $currentStageIndex = $index;
                                            // Count current stage as half complete
                                            $completedStages += 0.5;
                                        }
                                    }

                                    $progressPercent = $totalStages > 0 ? ($completedStages / $totalStages) * 100 : 0;

                                    // Background track
                                    $content .= "<div class='absolute h-2 bg-gray-200 dark:bg-gray-700 rounded-full w-full top-5 left-0 mt-0.5'></div>";

                                    // Progress overlay
                                    if ($progressPercent > 0) {
                                        $content .= "<div class='absolute h-2 bg-blue-500 dark:bg-blue-600 rounded-full top-5 left-0 mt-0.5' style='width: {$progressPercent}%;'></div>";
                                    }

                                    // Stages
                                    $content .= "<div class='relative flex justify-between items-start'>";

                                    foreach ($stages as $index => $stage) {
                                        // Calculate position percentage for non-edge elements (first and last are fixed at 0% and 100%)
                                        $positionStyle = '';
                                        if ($index > 0 && $index < (count($stages) - 1)) {
                                            $position = ($index / (count($stages) - 1)) * 100;
                                            $positionStyle = "position: absolute; left: {$position}%;";
                                        }

                                        $dotColor = match ($stage['status']) {
                                            'completed' => 'bg-green-500 border-green-600',
                                            'current' => 'bg-blue-500 border-blue-600',
                                            default => 'bg-gray-200 dark:bg-gray-600 border-gray-300 dark:border-gray-500'
                                        };

                                        $textColor = match ($stage['status']) {
                                            'completed' => 'text-green-600 dark:text-green-400',
                                            'current' => 'text-blue-600 dark:text-blue-400 font-medium',
                                            default => 'text-gray-500 dark:text-gray-400'
                                        };

                                        $stageClass = $index === 0 ? 'origin-left' : ($index === (count($stages) - 1) ? 'origin-right text-right' : 'text-center');

                                        if ($index === 0) {
                                            $content .= "<div class='relative {$stageClass}' style='z-index: 20;{$positionStyle}'>";
                                        } elseif ($index === (count($stages) - 1)) {
                                            $content .= "<div class='relative ml-auto {$stageClass}' style='z-index: 20;{$positionStyle}'>";
                                        } else {
                                            $content .= "<div class='relative {$stageClass}' style='z-index: 20;{$positionStyle}'>";
                                        }

                                        // Stage dot
                                        $content .= "<div class='h-5 w-5 rounded-full {$dotColor} border-2 mb-1 flex items-center justify-center mx-auto'>";

                                        // Add checkmark for completed stages
                                        if ($stage['status'] === 'completed') {
                                            $content .= "<svg class='h-3 w-3 text-white' viewBox='0 0 20 20' fill='currentColor'><path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'></path></svg>";
                                        }
                                        $content .= "</div>";

                                        // Stage name and date (shown below the timeline)
                                        $content .= "<div class='mt-2'>";
                                        $content .= "<p class='text-xs {$textColor} font-medium mb-1'>{$stage['name']}</p>";

                                        if ($stage['date']) {
                                            $content .= "<p class='text-xs text-gray-400'>{$stage['date']}</p>";
                                        }

                                        $content .= "</div>";
                                        $content .= "</div>";
                                    }

                                    $content .= "</div>";
                                    $content .= "</div>";
                                    $content .= "</div>";
                                }

                                return new HtmlString($content);
                            }),
                    ]),

                Wizard::make([
                    // Step 1: Submission Details
                    Step::make('Submission Details')
                        ->icon('heroicon-o-information-circle')
                        ->description('Review basic submission information')
                        ->schema(function () {
                            // Reference the submission details service for clean, reusable code
                            // Use Form components instead of Infolist components
                            $components = [];

                            // Add general information section
                            $components[] = $this->submissionDetailsService->getGeneralInfoFormSection($this->record);

                            // Add type-specific details section if available
                            $typeDetails = $this->submissionDetailsService->getTypeDetailsFormSection($this->record);
                            if ($typeDetails) {
                                $components[] = $typeDetails;
                            }

                            return $components;
                        }),

                    // Step 2: Document Review
                    Step::make('Document Review')
                        ->icon('heroicon-o-document-duplicate')
                        ->description('Review and evaluate submitted documents')
                        ->schema(function () {
                            $schemaItems = [];

                            if (!$this->record->currentStage) {
                                $schemaItems[] = Placeholder::make('no_stage')
                                    ->content(new HtmlString('<div class="italic text-center py-4 text-gray-500">No stage assigned to this submission</div>'))
                                    ->columnSpanFull();
                                return $schemaItems;
                            }

                            // Get stage requirements
                            $stageRequirements = $this->record->currentStage->stageRequirements()
                                ->pluck('document_requirement_id')
                                ->toArray();

                            // Display stage requirement satisfaction status
                            $schemaItems[] = Placeholder::make('stage_requirements_status')
                                ->content(function () use ($stageRequirements) {
                                    if (empty($stageRequirements)) {
                                        return new HtmlString(
                                            '<div class="p-4 bg-green-50 border border-green-200 rounded-xl mb-4 shadow-sm">
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-green-800">No specific document requirements for this stage</span>
                                                </div>
                                            </div>'
                                        );
                                    }

                                    if ($this->stageRequirementsSatisfied) {
                                        return new HtmlString(
                                            '<div class="p-4 bg-green-50 border border-green-200 rounded-xl mb-4 shadow-sm">
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-green-800">All stage requirements are satisfied</span>
                                                </div>
                                                <p class="text-green-700 mt-1">You can approve this submission to advance to the next stage</p>
                                            </div>'
                                        );
                                    } else {
                                        return new HtmlString(
                                            '<div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-4 shadow-sm">
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-yellow-800">Stage requirements not satisfied</span>
                                                </div>
                                                <p class="text-yellow-700 mt-1">You need to approve at least one document for each stage requirement before advancing this submission</p>
                                            </div>'
                                        );
                                    }
                                })
                                ->columnSpanFull();

                            // Get documents for this submission
                            $documents = $this->record->submissionDocuments()
                                ->with(['document', 'requirement'])
                                ->get();

                            // Group documents by requirement for better organization
                            $documentsByRequirement = [];
                            foreach ($documents as $doc) {
                                $requirementId = $doc->requirement_id;
                                $submissionDocumentId = $doc->id;
                                if (!isset($documentsByRequirement[$requirementId])) {
                                    $documentsByRequirement[$requirementId] = [
                                        'requirement' => $doc->requirement,
                                        'is_stage_requirement' => in_array($requirementId, $stageRequirements),
                                        'documents' => []
                                    ];
                                }
                                $documentsByRequirement[$requirementId]['documents'][] = $doc;
                            }

                            // Create a card for each requirement group
                            foreach ($documentsByRequirement as $requirementId => $group) {
                                $requirement = $group['requirement'];
                                $isStageRequirement = $group['is_stage_requirement'];
                                $docs = $group['documents'];

                                $badge = $isStageRequirement
                                    ? " <span class='bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded ml-2'>Stage Requirement</span>"
                                    : "";

                                $title = ($requirement->name ?? 'Unknown Requirement') . $badge;

                                // For each document in the group
                                $documentsList = '';
                                $documentComponents = [];

                                foreach ($docs as $doc) {
                                    // Skip if doc is null or invalid
                                    if (!$doc || !is_object($doc)) {
                                        continue;
                                    }
                                    
                                    $statusColor = match ($doc->status ?? 'pending') {
                                        'pending' => 'gray',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'revision_needed' => 'warning',
                                        default => 'gray',
                                    };

                                    $statusIcon = match ($doc->status ?? 'pending') {
                                        'approved' => '<svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                        'rejected' => '<svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                                        'revision_needed' => '<svg class="w-4 h-4 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                                        default => '<svg class="w-4 h-4 text-gray-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>',
                                    };

                                    $documentsList .= "
                                        <div class='p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm mb-3'>
                                            <div class='flex justify-between items-start'>
                                                <div>
                                                    <h4 class='text-base font-medium'>" . ($doc->document ? $doc->document->title : 'Untitled Document') . "</h4>
                                                    <p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>
                                                        " . ($doc->document ? $doc->document->mimetype : 'unknown') . " · " . ($doc->document ? number_format($doc->document->size / 1024, 0) : '0') . " KB · Uploaded " . ($doc->created_at ? $doc->created_at->diffForHumans() : 'Unknown time') . "
                                                    </p>
                                                </div>
                                                <div class='flex items-center'>
                                                    <div class='flex items-center px-2 py-1 rounded-full bg-{$statusColor}-100 text-{$statusColor}-800 mr-3'>
                                                        {$statusIcon}
                                                        <span class='text-xs font-medium'>" . ucfirst($doc->status ?? 'pending') . "</span>
                                                    </div>
                                                    <a href='" . route('filament.admin.documents.download', $doc->document_id ?? 'unknown') . "' 
                                                       target='_blank' 
                                                       class='inline-flex items-center px-2.5 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500'>
                                                        <svg class='h-4 w-4 mr-1' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path>
                                                        </svg>
                                                        Download
                                                    </a>
                                                </div>
                                            </div>";

                                    // Add notes if present
                                    if (!empty($doc->notes ?? '')) {
                                        $documentsList .= "
                                            <div class='mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded border-l-4 border-{$statusColor}-400'>
                                                <h5 class='text-xs font-medium mb-1'>Review Notes:</h5>
                                                <p class='text-sm'>" . e($doc->notes) . "</p>
                                            </div>";
                                    }

                                    $documentsList .= "
                                            <div class='mt-3'>
                                                <div class='flex items-center space-x-2'>" .
                                        ($isStageRequirement ?
                                            "<span class='flex items-center " . (($doc->status ?? 'pending') === 'approved' ? 'text-green-600' : 'text-blue-600') . " text-xs'>
                                                        " . (($doc->status ?? 'pending') === 'approved' ?
                                                '<svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> This document is approved and satisfies stage requirements' :
                                                '<svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg> This document requires review for stage progression') . "
                                                    </span>"
                                            :
                                            "") .
                                        "</div>
                                            </div>
                                        </div>";

                                    // Create individual select components for each document using document ID
                                    $documentTitle = $doc->document ? $doc->document->title : 'Untitled Document';
                                    $documentId = $doc->id ?? 'unknown';
                                    $documentStatus = $doc->status ?? 'pending';
                                    
                                    $documentComponents[] = Select::make("document_status_{$documentId}")
                                        ->label("Update Status for: {$documentTitle}")
                                        ->options([
                                            'pending' => 'Pending Review',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'revision_needed' => 'Revision Needed',
                                        ])
                                        ->default($documentStatus)
                                        ->reactive();
                                            
                                   }

                                $schemaItems[] = Section::make(new HtmlString($title))
                                    ->description(function () use ($requirement) {
                                        return $requirement->description ?? '';
                                    })
                                    ->schema([
                                        Placeholder::make("documents_for_requirement_{$requirementId}")
                                            ->content(new HtmlString($documentsList)),

                                        // Add individual document components
                                        ...$documentComponents
                                    ])
                                    ->collapsible();
                            }

                            if (count($documentsByRequirement) === 0) {
                                $schemaItems[] = Placeholder::make('no_documents')
                                    ->content(new HtmlString('<div class="italic text-center py-4 text-gray-500">No documents have been uploaded for this submission</div>'))
                                    ->columnSpanFull();
                            }

                            return $schemaItems;
                        }),

                    // Step 3: Review Decision
                    Step::make('Final Decision')
                        ->icon('heroicon-o-check-circle')
                        ->description('Submit your final review decision')
                        ->schema([
                            Section::make('Review Decision')
                                ->description('Make your final decision about this submission')
                                ->schema([
                                    Select::make('reviewDecision')
                                        ->label('Decision')
                                        ->options([
                                            'approved' => 'Approve - Move to Next Stage',
                                            'revision_needed' => 'Request Revision',
                                            'rejected' => 'Reject Submission',
                                        ])
                                        ->required()
                                        ->reactive()
                                        ->disabled(!$this->stageRequirementsSatisfied && $this->record->currentStage && !$this->record->currentStage->stageRequirements()->exists())
                                        ->helperText(function () {
                                            if (!$this->stageRequirementsSatisfied && $this->record->currentStage && $this->record->currentStage->stageRequirements()->exists()) {
                                                return 'You need to approve all stage requirements before approving this submission';
                                            }
                                            return null;
                                        }),
                                        Textarea::make('reviewer_notes')
                                        ->label('Comments')
                                        ->placeholder('Enter your review comments, feedback, or reasons for your decision')
                                        ->required()
                                        ->rows(4),
                                    // Certificate Upload Section - Only show when final stage and approved
                                    Section::make('📜 Upload Sertifikat')
                                        ->description('Upload sertifikat untuk menyelesaikan proses pengajuan')
                                        ->schema([
                                            Placeholder::make('certificate_info')
                                                ->content(new HtmlString('
                                                    <div class="p-4 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-xl mb-4">
                                                        <div class="flex items-center mb-3">
                                                            <span class="text-2xl mr-3">🎯</span>
                                                            <div>
                                                                <h3 class="text-lg font-semibold text-emerald-800">Tahap Final - Upload Sertifikat</h3>
                                                                <p class="text-emerald-700">Ini adalah tahap terakhir dalam proses pengajuan.</p>
                                                            </div>
                                                        </div>
                                                        <div class="bg-white rounded-lg p-3 border border-emerald-100">
                                                            <p class="text-emerald-800 text-sm">
                                                                <span class="font-medium">📋 Petunjuk:</span>
                                                                Upload file sertifikat resmi untuk menyelesaikan pengajuan ini. 
                                                                Status akan berubah menjadi "Completed" setelah sertifikat diupload.
                                                            </p>
                                                        </div>
                                                    </div>
                                                '))
                                                ->visible(function () {
                                                    return $this->record && 
                                                           $this->record->currentStage && 
                                                           $this->record->currentStage->isFinalStage();
                                                }),

                                            FileUpload::make('certificate_file')
                                                ->label('📄 File Sertifikat')
                                                ->disk('public')
                                                ->directory('certificates')
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->maxSize(5120) // 5MB
                                                ->downloadable()
                                                ->previewable()
                                                ->imagePreviewHeight('300')
                                                ->helperText('
                                                    📌 Format yang diperbolehkan: PDF, JPG, PNG (maksimal 5MB)
                                                    💡 Tip: Pastikan file sertifikat dapat dibaca dengan jelas
                                                ')
                                                ->placeholder('Klik di sini untuk memilih file sertifikat...')
                                                ->visible(function () {
                                                    return $this->record && 
                                                           $this->record->currentStage && 
                                                           $this->record->currentStage->isFinalStage() &&
                                                           isset($this->data['reviewDecision']) &&
                                                           $this->data['reviewDecision'] === 'approved';
                                                })
                                                ->required(function () {
                                                    return $this->record && 
                                                           $this->record->currentStage && 
                                                           $this->record->currentStage->isFinalStage() &&
                                                           isset($this->data['reviewDecision']) &&
                                                           $this->data['reviewDecision'] === 'approved';
                                                }),

                                            TextInput::make('certificate_number')
                                                ->label('🔢 Nomor Sertifikat')
                                                ->placeholder('Contoh: CERT-2024-001')
                                                ->maxLength(100)
                                                ->helperText('Masukkan nomor sertifikat resmi (opsional - akan digenerate otomatis jika kosong)')
                                                ->visible(function () {
                                                    return $this->record && 
                                                           $this->record->currentStage && 
                                                           $this->record->currentStage->isFinalStage() &&
                                                           isset($this->data['reviewDecision']) &&
                                                           $this->data['reviewDecision'] === 'approved';
                                                }),

                                            Placeholder::make('certificate_warning')
                                                ->content(new HtmlString('
                                                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                                        <div class="flex items-center">
                                                            <span class="text-xl mr-2">⚠️</span>
                                                            <div>
                                                                <p class="text-amber-800 font-medium text-sm">Peringatan Penting</p>
                                                                <p class="text-amber-700 text-sm mt-1">
                                                                    Setelah sertifikat diupload dan pengajuan diselesaikan, 
                                                                    status tidak dapat diubah lagi. Pastikan semua informasi sudah benar.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                '))
                                                ->visible(function () {
                                                    return $this->record && 
                                                           $this->record->currentStage && 
                                                           $this->record->currentStage->isFinalStage() &&
                                                           isset($this->data['reviewDecision']) &&
                                                           $this->data['reviewDecision'] === 'approved';
                                                }),
                                        ])
                                        ->visible(function () {
                                            return $this->record && 
                                                   $this->record->currentStage && 
                                                   $this->record->currentStage->isFinalStage();
                                        }),

                                ]),

                            Section::make()
                                ->schema([
                                    Placeholder::make('submit_review_info')
                                        ->content(new HtmlString('
                                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-blue-800">Ready to submit your review?</span>
                                                </div>
                                                <p class="ml-7 text-blue-700 text-sm mt-1">Click the button below to submit your review decision.</p>
                                            </div>
                                        ')),

                                    Placeholder::make('submit_button_container')
                                        ->content(function () {
                                            // Check if requirements are satisfied for approval
                                            $disabledReason = '';
                                            $disabled = false;

                                            if (
                                                !$this->stageRequirementsSatisfied &&
                                                $this->record->currentStage &&
                                                $this->record->currentStage->stageRequirements()->exists() &&
                                                isset($this->data['reviewDecision']) &&
                                                $this->data['reviewDecision'] === 'approved'
                                            ) {
                                                $disabled = true;
                                                $disabledReason = 'You need to approve all required documents before approving this submission.';
                                            }

                                            $buttonHtml = '
                                                <div class="flex justify-end">
                                                    <button type="submit" ' . ($disabled ? 'disabled' : '') . ' 
                                                    class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg 
                                                    border transition-colors focus:outline-none focus:ring-offset-2 
                                                    focus:ring-2 focus:ring-inset filament-button dark:focus:ring-offset-0 
                                                    h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent 
                                                    bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 
                                                    ' . ($disabled ? 'opacity-70 cursor-not-allowed' : '') . '">
                                                    <svg class="h-5 w-5 -ml-1 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Submit Review
                                                    </button>
                                                </div>';

                                            if ($disabled) {
                                                $buttonHtml .= '
                                                    <p class="text-sm text-red-600 mt-2">
                                                        ' . $disabledReason . '
                                                    </p>';
                                            }

                                            return new HtmlString($buttonHtml);
                                        })
                                ])
                        ]),
                ])
                    ->columnSpanFull()
            ])
            ->statePath('data');
    }

    public function submitReview()
    {
        // Validate basic review data
        try {
            $this->validate([
                'data.reviewDecision' => 'required|in:approved,revision_needed,rejected',
                'data.reviewer_notes' => 'required|string|min:10',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title('Validation Error')
                ->body('Please check your review decision and ensure comments are at least 10 characters.')
                ->danger()
                ->send();

            throw $e;
        }

        try {
            DB::beginTransaction();

            // Check if the submission is already in the final stage
            if ($this->record->current_stage_id === $this->record->submissionType->final_stage_id) {
                DB::rollBack();

                Notification::make()
                    ->title('Cannot approve submission')
                    ->body('This submission is already in the final stage and cannot be approved further.')
                    ->danger()
                    ->send();

                return;
            }
            // Check if the submission is already in the final status
            if ($this->record->status === 'completed') {
                DB::rollBack();

                Notification::make()
                    ->title('Cannot approve submission')
                    ->body('This submission is already completed and cannot be approved further.')
                    ->danger()
                    ->send();

                return;
            }

            // Recalculate stage requirement satisfaction
            $this->checkStageRequirementsSatisfaction();


            // Check if each requirement has at least one approved document
            $allRequirementsSatisfied = $this->stageRequirementsSatisfied;


            // Check if we can approve when that's the decision
            if ($this->data['reviewDecision'] === 'approved') {
                // First validate stage requirements
                if (
                    !$this->stageRequirementsSatisfied &&
                    $this->record->currentStage &&
                    $this->record->currentStage->stageRequirements()->exists()
                ) {

                    DB::rollBack();

                    Notification::make()
                        ->title('Cannot approve submission')
                        ->body('You need to approve all required documents for this stage before approving the submission')
                        ->danger()
                        ->send();

                    return;
                }

                // Then validate that at least one document for each requirement is approved
                if (!$allRequirementsSatisfied) {
                    DB::rollBack();

                    Notification::make()
                        ->title('Cannot approve submission')
                        ->body('You need to approve at least one document for each document requirement before approving the submission')
                        ->danger()
                        ->send();

                    return;
                }
            }

            // Get the current assignment or create one if none exists
            $currentAssignment = WorkflowAssignment::where('submission_id', $this->record->id)
                ->where('stage_id', $this->record->current_stage_id)
                ->where('status', 'pending')
                ->first();

            if (!$currentAssignment) {
                $currentAssignment = WorkflowAssignment::create([
                    'id' => Str::uuid()->toString(),
                    'submission_id' => $this->record->id,
                    'stage_id' => $this->record->current_stage_id,
                    'reviewer_id' => Auth::id(),
                    'assigned_by' => Auth::id(),
                    'status' => 'pending',
                    'assigned_at' => now(),
                ]);
            }

            // Update current assignment status
            $currentAssignment->update([
                'status' => $this->data['reviewDecision'],
                'notes' => $this->data['reviewer_notes'],
                'completed_at' => now(),
            ]);

            // Store reviewer notes on submission if provided (for revision_needed or rejected status)
            if (in_array($this->data['reviewDecision'], ['revision_needed', 'rejected'])) {
                $reviewerNotes = $this->data['reviewer_notes'] ?? null;

                if (!empty($reviewerNotes)) {
                    $this->record->update([
                        'reviewer_notes' => $reviewerNotes,
                    ]);
                }
            }

            // Create tracking history with standardized fields
            TrackingHistory::create([
                'id' => Str::uuid()->toString(),
                'submission_id' => $this->record->id,
                'stage_id' => $this->record->current_stage_id,
                'action' => 'review_' . $this->data['reviewDecision'], // Precise action verb
                'status' => match ($this->data['reviewDecision']) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    'revision_needed' => 'revision_needed',
                    default => 'in_progress',
                }, // Convert to standard status enum
                'comment' => $this->data['reviewer_notes'], // User-provided feedback
                'processed_by' => Auth::id(), // Who performed the action
                'source_status' => $this->record->status, // Status before the change
                'target_status' => $this->data['reviewDecision'], // Status after the change
                'event_type' => 'review_decision', // Consistent event type
                'metadata' => [
                    'reviewer_role' => Auth::user()->roles->pluck('name')->first(),
                    'submission_type' => $this->record->submissionType->slug,
                    'decision_context' => $allRequirementsSatisfied ? 'requirements_satisfied' : 'requirements_waived',
                    'has_reviewer_notes' => !empty($this->data['reviewer_notes']),
                ],
                'event_timestamp' => now(), // When this event occurred
            ]);

            $currentStageName = $this->record->currentStage?->name ?? 'Current Stage';

            // Handle based on decision
            switch ($this->data['reviewDecision']) {
                case 'approved':
                    // Check if this is the final stage
                    if ($this->record->currentStage && $this->record->currentStage->isFinalStage()) {
                        // Handle final stage completion with certificate
                        $certificateFile = $this->data['certificate_file'] ?? null;
                        $certificateNumber = $this->data['certificate_number'] ?? null;
                        
                        if (!$certificateFile) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('❌ Sertifikat Diperlukan')
                                ->body('Harap upload file sertifikat untuk menyelesaikan pengajuan ini.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Generate certificate number if not provided
                        if (!$certificateNumber) {
                            $certificateNumber = 'CERT-' . strtoupper($this->record->submissionType->slug) . '-' . 
                                               date('Y') . '-' . str_pad($this->record->id, 4, '0', STR_PAD_LEFT);
                        }
                        
                        // Update submission to completed status
                        $this->record->update([
                            'status' => 'completed',
                            'certificate' => $certificateFile, // Store file path
                            'reviewer_notes' => $this->data['reviewer_notes'],
                        ]);
                        
                        // Create tracking entry for completion
                        TrackingHistory::create([
                            'id' => Str::uuid()->toString(),
                            'submission_id' => $this->record->id,
                            'stage_id' => $this->record->current_stage_id,
                            'action' => 'complete_submission',
                            'status' => 'completed',
                            'comment' => 'Pengajuan telah selesai dan sertifikat telah diterbitkan.',
                            'processed_by' => Auth::id(),
                            'metadata' => [
                                'certificate_file' => $certificateFile,
                                'certificate_number' => $certificateNumber,
                                'completion_date' => now()->toDateString(),
                            ],
                            'event_timestamp' => now(),
                        ]);
                        
                        // Send completion notification
                        $this->record->user->notify(new ReviewActionNotification(
                            $this->record,
                            "🎉 Pengajuan Anda Telah Selesai!",
                            "Selamat! Pengajuan '{$this->record->title}' telah berhasil diselesaikan. Sertifikat dengan nomor {$certificateNumber} telah diterbitkan dan dapat diunduh dari halaman pengajuan Anda."
                        ));
                        
                        break;
                    }
                    
                    // Check if submission can advance to next stage
                    if (!$this->record->canAdvanceToNextStage()) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Cannot approve submission')
                            ->body('This submission cannot be advanced to the next stage. The workflow may have ended or there is no valid next stage.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get the next stage using the Submission model's method
                    $nextStage = $this->record->nextStage();
                    if (!$nextStage) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Cannot approve submission')
                            ->body('No next stage found in the workflow. Please check the workflow configuration.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get the next stage ID
                    $nextStageId = $nextStage->id;

                    // Update submission to new stage
                    $this->record->update([
                        'status' => 'in_review', // Keep as in_review for the next stage
                        'current_stage_id' => $nextStageId,
                        'is_active' => true, // Ensure is_active is true for approved status
                    ]);

                    // Check if an assignment already exists for this submission, stage, and reviewer
                    $existingAssignment = WorkflowAssignment::where('submission_id', $this->record->id)
                        ->where('stage_id', $nextStageId)
                        ->where('reviewer_id', Auth::id())
                        ->first();

                    // Only create a new assignment if one doesn't already exist
                    if (!$existingAssignment) {
                        WorkflowAssignment::create([
                            'id' => Str::uuid()->toString(),
                            'submission_id' => $this->record->id,
                            'stage_id' => $nextStageId,
                            'reviewer_id' => Auth::id(),
                            'assigned_by' => Auth::id(),
                            'status' => 'pending',
                            'assigned_at' => now(),
                        ]);
                    } else {
                        // Update the existing assignment if needed
                        $existingAssignment->update([
                            'status' => 'pending',
                            'assigned_at' => now(),
                        ]);
                    }

                    // Get next stage name
                    $nextStageName = $nextStage->name ?? 'Next Stage';

                    // Send notification to the next reviewer
                    $nextReviewer = \App\Models\User::find(Auth::id());
                    $nextReviewer->notify(new ReviewActionNotification(
                        $this->record,
                        "New submission assigned to you for review",
                        "A submission titled '{$this->record->title}' has been approved by previous stage '{$currentStageName}' and is now assigned to you for review in stage '{$nextStageName}'."
                    ));

                    // Send notification to the submitter
                    $this->record->user->notify(new ReviewActionNotification(
                        $this->record,
                        "Your submission has moved to the next stage",
                        "Your submission titled '{$this->record->title}' has been approved by '{$currentStageName}' and has moved to '{$nextStageName}' for further review."
                    ));
                    break;

                case 'revision_needed':
                    // Update submission status
                    $this->record->update([
                        'status' => 'revision_needed',
                        'is_active' => false, // Set is_active to false for status other than pending or approved
                    ]);

                    // Send notification to the submitter
                    $this->record->user->notify(new ReviewActionNotification(
                        $this->record,
                        "Revision needed for your submission",
                        "Your submission titled '{$this->record->title}' requires revision. Comments: {$this->data['reviewer_notes']}"
                    ));
                    break;

                case 'rejected':
                    // Update submission status
                    $this->record->update([
                        'status' => 'rejected',
                        'is_active' => false, // Set is_active to false for status other than pending or approved
                    ]);

                    // Send notification to the submitter
                    $this->record->user->notify(new ReviewActionNotification(
                        $this->record,
                        "Your submission has been rejected",
                        "We regret to inform you that your submission titled '{$this->record->title}' has been rejected. Reason: {$this->data['reviewer_notes']}"
                    ));
                    break;
            }

            DB::commit();

            Notification::make()
                ->title('Review submitted successfully')
                ->success()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error submitting review')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Process document status update with confirmation
     * 
     * @param string $documentId
     * @param string $status
     * @param string|null $notes
     */
    public function updateDocumentStatus(string $documentId, string $status, ?string $notes = null): void
    {
        try {
            // Validate documentId format
            if (!preg_match('/^[0-9a-f-]{36}$/i', $documentId)) {
                throw new \Exception("Invalid document ID format");
            }

            // Find the submission document
            $document = $this->record->submissionDocuments()
                ->where('id', $documentId)
                ->first();

            if (!$document) {
                // Try a direct lookup if relation query fails
                $document = \App\Models\SubmissionDocument::find($documentId);

                // If document exists but doesn't belong to this submission, reject the update
                if ($document && $document->submission_id !== $this->record->id) {
                    throw new \Exception("Document does not belong to this submission");
                }
            }

            if (!$document) {
                throw new \Exception("Document not found with ID: {$documentId}");
            }

            // Update the document status in the database
            $document->update([
                'status' => $status,
                'notes' => $notes,
            ]);

            // Update local document statuses for form state
            $this->documentStatuses["document_{$document->id}"] = $status;

            // Recalculate stage requirement satisfaction
            $this->checkStageRequirementsSatisfaction();

            // Show success notification
            $statusLabel = ucfirst(str_replace('_', ' ', $status));
            Notification::make()
                ->title("Document marked as {$statusLabel}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Add more detailed error notification
            Notification::make()
                ->title('Error updating document status')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Log the error
            \Illuminate\Support\Facades\Log::error('Document status update failed', [
                'document_id' => $documentId,
                'status' => $status,
                'error' => $e->getMessage(),
                'submission_id' => $this->record?->id,
            ]);
        }
    }
}
