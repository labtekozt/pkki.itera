<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingHistoryController extends Controller
{
    /**
     * Display detailed tracking history with advanced filtering
     */
    public function detail(Request $request)
    {
        // Get filter parameters from request
        $submissionId = $request->input('submission_id');
        $documentId = $request->input('document_id');
        $userId = $request->input('user_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $eventTypes = $request->input('event_types', []);
        $statuses = $request->input('statuses', []);

        // Start building the query with filterable conditions
        $query = TrackingHistory::with(['submission', 'stage', 'previousStage', 'document', 'processor'])
            ->when($submissionId, function ($query) use ($submissionId) {
                return $query->where('submission_id', $submissionId);
            })
            ->when($documentId, function ($query) use ($documentId) {
                return $query->where('document_id', $documentId);
            })
            ->when($userId, function ($query) use ($userId) {
                return $query->where('processed_by', $userId);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->when(!empty($eventTypes), function ($query) use ($eventTypes) {
                return $query->whereIn('event_type', $eventTypes);
            })
            ->when(!empty($statuses), function ($query) use ($statuses) {
                return $query->whereIn('status', $statuses);
            })
            ->orderBy('created_at', 'desc');

        // Get the paginated results
        $trackingHistories = $query->paginate(15)->withQueryString();

        // Load options for filters
        $submissionOptions = Submission::select('id', 'title')->orderBy('title')->get();
        $documentOptions = Document::select('id', 'title')->orderBy('title')->get();
        $userOptions = User::select('id', 'name', DB::raw("CONCAT(name, ' ', COALESCE(last_name, '')) as fullname"))
            ->orderBy('name')
            ->get();

        // Event types and statuses for checkboxes
        $eventTypeOptions = [
            'state_change', 'transition', 'stage_transition', 'revision_request', 
            'revision_submission', 'rejection', 'approval', 'completion', 'document_upload'
        ];
        
        $statusOptions = [
            'started', 'in_progress', 'approved', 'rejected', 'revision_needed', 'completed'
        ];

        // If no event types selected, show all
        if (empty($eventTypes)) {
            $eventTypes = $eventTypeOptions;
        }

        // If no statuses selected, show all
        if (empty($statuses)) {
            $statuses = $statusOptions;
        }

        return view('tracking.detail', compact(
            'trackingHistories',
            'submissionOptions',
            'documentOptions',
            'userOptions',
            'eventTypeOptions',
            'statusOptions',
            'submissionId',
            'documentId',
            'userId',
            'startDate',
            'endDate',
            'eventTypes',
            'statuses'
        ));
    }

    /**
     * Display timeline view for a specific submission
     */
    public function timeline(Request $request, $submissionId)
    {
        $submission = Submission::with(['submissionType', 'currentStage', 'user'])
            ->findOrFail($submissionId);

        // Get all tracking history for this submission, ordered by date
        $trackingHistory = $submission->trackingHistory()
            ->with(['stage', 'previousStage', 'document', 'processor'])
            ->orderBy('created_at')
            ->get();

        // Organize events by stage
        $timeline = [];
        $currentStageId = null;
        $stageGroup = null;

        foreach ($trackingHistory as $event) {
            // If this is a new stage, create a new group
            if ($event->stage_id !== $currentStageId) {
                // Save the previous group if it exists
                if ($stageGroup) {
                    $timeline[] = $stageGroup;
                }

                // Start a new stage group
                $currentStageId = $event->stage_id;
                $stageGroup = [
                    'stage_id' => $event->stage_id,
                    'stage_name' => $event->stage->name ?? 'Unknown Stage',
                    'start_date' => $event->created_at,
                    'end_date' => null,
                    'events' => []
                ];
            }

            // Add this event to the current stage group
            $isTransition = $event->previous_stage_id !== null;
            $stageGroup['events'][] = [
                'id' => $event->id,
                'action' => $event->action,
                'status' => $event->status,
                'event_type' => $event->event_type,
                'comment' => $event->comment,
                'date' => $event->created_at,
                'processor_name' => $event->processor->fullname ?? null,
                'is_transition' => $isTransition,
                'transition_from' => $isTransition ? ($event->previousStage->name ?? 'Unknown') : null,
                'has_document' => $event->document_id !== null,
                'document_title' => $event->document->title ?? null,
            ];

            // If this event is a stage transition, set the end date for this stage
            if ($isTransition) {
                $stageGroup['end_date'] = $event->created_at;
            }
        }

        // Add the last stage group
        if ($stageGroup) {
            // If the stage is still active, set end_date to null
            if ($stageGroup['stage_id'] === $submission->current_stage_id) {
                $stageGroup['end_date'] = null;
            }
            $timeline[] = $stageGroup;
        }

        return view('tracking.timeline', compact('submission', 'timeline'));
    }

    /**
     * Display a summary dashboard of tracking statistics
     */
    public function dashboard()
    {
        // Recent activity
        $recentActivity = TrackingHistory::with(['submission', 'stage', 'processor'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Count by status
        $statusCounts = TrackingHistory::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Count by action
        $actionCounts = TrackingHistory::select('action', DB::raw('count(*) as total'))
            ->groupBy('action')
            ->get()
            ->pluck('total', 'action')
            ->toArray();

        // Activity trend by date (last 30 days)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $activityTrend = TrackingHistory::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top users by activity
        $topUsers = TrackingHistory::select('processed_by', DB::raw('count(*) as total'))
            ->whereNotNull('processed_by')
            ->groupBy('processed_by')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->with('processor')
            ->get();

        return view('tracking.dashboard', compact(
            'recentActivity',
            'statusCounts',
            'actionCounts',
            'activityTrend',
            'topUsers'
        ));
    }
}