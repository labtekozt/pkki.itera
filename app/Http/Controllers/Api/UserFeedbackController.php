<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserFeedbackController extends Controller
{
    /**
     * Store user feedback for elderly-friendly interface improvements
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|between:1,5',
                'difficult_areas' => 'array',
                'difficult_areas.*' => 'string|in:navigation,forms,upload,understanding,text_size,buttons',
                'age_range' => 'nullable|string|in:under_30,30_40,40_50,50_60,60_70,over_70',
                'tech_comfort' => 'nullable|string|in:very_comfortable,comfortable,not_comfortable,need_help',
                'device_type' => 'nullable|string|in:desktop,tablet,smartphone',
                'comments' => 'nullable|string|max:1000',
                'contact_permission' => 'boolean',
                'page_url' => 'required|string',
                'user_agent' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $feedbackData = $validator->validated();
            
            // Add additional context
            $feedbackData['user_id'] = auth()->id(); // Can be null for anonymous feedback
            $feedbackData['session_id'] = session()->getId();
            $feedbackData['ip_address'] = $request->ip();
            $feedbackData['submitted_at'] = now();

            // Store in database using the model's fillable fields
            $feedback = UserFeedback::create([
                'user_id' => $feedbackData['user_id'], // Can be null
                'rating' => $feedbackData['rating'],
                'difficulty_areas' => $feedbackData['difficult_areas'] ?? [],
                'age_range' => $feedbackData['age_range'] ?? null,
                'tech_comfort' => $feedbackData['tech_comfort'] ?? null,
                'device_type' => $feedbackData['device_type'] ?? null,
                'comments' => $feedbackData['comments'] ?? null,
                'page_url' => $feedbackData['page_url'],
                'page_title' => $request->input('page_title'),
                'browser_info' => [
                    'user_agent' => $feedbackData['user_agent'] ?? $request->header('User-Agent'),
                    'ip_address' => $feedbackData['ip_address'],
                    'session_id' => $feedbackData['session_id'],
                ],
                'contact_permission' => $feedbackData['contact_permission'] ?? false,
            ]);

            // Log for analytics
            Log::channel('user_feedback')->info('User feedback received', [
                'feedback_id' => $feedback->id,
                'rating' => $feedback->rating,
                'age_range' => $feedback->age_range,
                'tech_comfort' => $feedback->tech_comfort,
                'device_type' => $feedback->device_type,
                'page_url' => $feedback->page_url,
            ]);

            // Send notification to admin if rating is low (1-2 stars) or specific issues
            if ($feedback->overall_rating <= 2 || $this->hasAccessibilityIssues($feedbackData['difficult_areas'] ?? [])) {
                $this->notifyAdminOfCriticalFeedback($feedback);
            }

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih atas feedback Anda! Masukan Anda sangat berharga untuk pengembangan sistem.',
                'feedback_id' => $feedback->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error storing user feedback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan feedback. Silakan coba lagi atau hubungi admin.'
            ], 500);
        }
    }

    /**
     * Get feedback analytics for admin dashboard
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            // Check if user has admin privileges
            if (!auth()->user() || !auth()->user()->can('view_analytics')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $period = $request->get('period', '30'); // Default 30 days
            $startDate = now()->subDays($period);

            $analytics = [
                'overview' => [
                    'total_feedback' => UserFeedback::where('created_at', '>=', $startDate)->count(),
                    'average_rating' => UserFeedback::where('created_at', '>=', $startDate)->avg('rating'),
                    'low_ratings_count' => UserFeedback::where('created_at', '>=', $startDate)->where('rating', '<=', 2)->count(),
                    'contact_permission_count' => UserFeedback::where('created_at', '>=', $startDate)->where('contact_permission', true)->count(),
                ],
                'rating_distribution' => UserFeedback::where('created_at', '>=', $startDate)
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->get(),
                'age_distribution' => UserFeedback::where('created_at', '>=', $startDate)
                    ->whereNotNull('age_range')
                    ->selectRaw('age_range, COUNT(*) as count')
                    ->groupBy('age_range')
                    ->get(),
                'tech_comfort_distribution' => UserFeedback::where('created_at', '>=', $startDate)
                    ->whereNotNull('tech_comfort')
                    ->selectRaw('tech_comfort, COUNT(*) as count')
                    ->groupBy('tech_comfort')
                    ->get(),
                'device_distribution' => UserFeedback::where('created_at', '>=', $startDate)
                    ->whereNotNull('device_type')
                    ->selectRaw('device_type, COUNT(*) as count')
                    ->groupBy('device_type')
                    ->get(),
                'common_issues' => $this->getCommonIssues($startDate),
                'recent_comments' => UserFeedback::where('created_at', '>=', $startDate)
                    ->whereNotNull('comments')
                    ->where('comments', '!=', '')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->select(['id', 'rating', 'comments', 'age_range', 'tech_comfort', 'created_at'])
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'period' => $period . ' days'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching feedback analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching analytics data'
            ], 500);
        }
    }

    /**
     * Export feedback data for further analysis
     */
    public function export(Request $request): JsonResponse
    {
        try {
            if (!auth()->user() || !auth()->user()->can('export_analytics')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $period = $request->get('period', '30');
            $startDate = now()->subDays($period);

            $feedbacks = UserFeedback::where('created_at', '>=', $startDate)
                ->with('user:id,fullname,email')
                ->get()
                ->map(function ($feedback) {
                    return [
                        'id' => $feedback->id,
                        'user_name' => $feedback->user->fullname ?? 'Anonymous',
                        'user_email' => $feedback->contact_permission ? ($feedback->user->email ?? '') : '[Hidden]',
                        'rating' => $feedback->rating,
                        'difficulty_areas' => $feedback->difficulty_areas,
                        'age_range' => $feedback->age_range,
                        'tech_comfort' => $feedback->tech_comfort,
                        'device_type' => $feedback->device_type,
                        'comments' => $feedback->comments,
                        'page_url' => $feedback->page_url,
                        'page_title' => $feedback->page_title,
                        'submitted_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Log export action
            Log::info('Feedback data exported', [
                'user_id' => auth()->id(),
                'period' => $period,
                'record_count' => $feedbacks->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
                'meta' => [
                    'total_records' => $feedbacks->count(),
                    'period' => $period . ' days',
                    'exported_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting feedback data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error exporting feedback data'
            ], 500);
        }
    }

    /**
     * Check if feedback contains accessibility issues
     */
    private function hasAccessibilityIssues(array $difficultAreas): bool
    {
        $accessibilityIssues = ['text_size', 'buttons', 'navigation', 'forms', 'error_messages'];
        return !empty(array_intersect($difficultAreas, $accessibilityIssues));
    }

    /**
     * Notify admin of critical feedback that needs immediate attention
     */
    private function notifyAdminOfCriticalFeedback(UserFeedback $feedback): void
    {
        // You can implement email notification, Slack notification, etc.
        Log::channel('critical_feedback')->warning('Critical user feedback received', [
            'feedback_id' => $feedback->id,
            'rating' => $feedback->rating,
            'issues' => $feedback->difficulty_areas,
            'user_info' => [
                'age_range' => $feedback->age_range,
                'tech_comfort' => $feedback->tech_comfort,
                'device_type' => $feedback->device_type,
            ],
            'comments' => $feedback->comments,
            'page_url' => $feedback->page_url,
        ]);

        // You could also send real-time notifications via WebSocket or SMS
    }

    /**
     * Get common issues from feedback data
     */
    private function getCommonIssues(\DateTime $startDate): array
    {
        $feedbacks = UserFeedback::where('created_at', '>=', $startDate)
            ->whereNotNull('difficulty_areas')
            ->pluck('difficulty_areas');

        $issueCount = [];
        foreach ($feedbacks as $difficultAreas) {
            $areas = is_array($difficultAreas) ? $difficultAreas : [];
            foreach ($areas as $area) {
                $issueCount[$area] = ($issueCount[$area] ?? 0) + 1;
            }
        }

        // Sort by frequency and return top issues
        arsort($issueCount);
        
        $issueLabels = [
            'navigation' => 'Navigasi/berpindah halaman',
            'forms' => 'Mengisi formulir',
            'upload' => 'Upload dokumen',
            'understanding' => 'Memahami instruksi',
            'text_size' => 'Ukuran teks terlalu kecil',
            'buttons' => 'Tombol sulit diklik',
        ];

        $result = [];
        foreach ($issueCount as $issue => $count) {
            $result[] = [
                'issue' => $issue,
                'label' => $issueLabels[$issue] ?? $issue,
                'count' => $count,
                'percentage' => round(($count / $feedbacks->count()) * 100, 1)
            ];
        }

        return array_slice($result, 0, 10); // Return top 10 issues
    }
}
