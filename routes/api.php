<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserFeedbackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User Feedback API Routes
Route::prefix('feedback')->group(function () {
    // Public endpoint for submitting feedback (no auth required for accessibility)
    Route::post('/submit', [UserFeedbackController::class, 'store']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        // Analytics endpoints for admin users
        Route::get('/analytics', [UserFeedbackController::class, 'analytics'])
            ->middleware('permission:view_user_feedback');
        
        Route::get('/analytics/detailed', [UserFeedbackController::class, 'detailedAnalytics'])
            ->middleware('permission:view_user_feedback');
        
        Route::get('/export', [UserFeedbackController::class, 'export'])
            ->middleware('permission:export_user_feedback');
        
        Route::get('/critical', [UserFeedbackController::class, 'getCriticalFeedback'])
            ->middleware('permission:view_user_feedback');
        
        // Mark feedback as processed
        Route::patch('/{feedback}/process', [UserFeedbackController::class, 'markAsProcessed'])
            ->middleware('permission:process_user_feedback');
    });
});
