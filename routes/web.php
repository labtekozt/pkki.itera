<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Models\Document;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\TrackingHistoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return Inertia::render(component: 'Welcome');
});
Route::get('/kegiatan', function () {
    return Inertia::render(component:'Kegiatan');  
});
Route::get('/news', function () {
    return Inertia::render(component:'news');  
});

// Add a 'login' named route that redirects to Filament login
Route::redirect('/login', '/admin/login')->name('login');

// Add document download route for admin panel
Route::get('/admin/documents/{document}/download', function (Document $document) {
    // Check permissions using Auth facade
    if (!Auth::check()) {
        abort(403, 'Unauthorized');
    }

    // Check if the file exists in the storage
    if (!Storage::disk('public')->exists($document->uri)) {
        abort(404, 'File not found');
    }

    // Return the file for download using Storage facade
    return Storage::disk('public')->download(
        $document->uri, 
        $document->title . '.' . $document->extension
    );
})->name('filament.admin.documents.download');

// Document routes
Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])
        ->name('filament.admin.documents.download');
    Route::get('/documents/{document}/view', [App\Http\Controllers\DocumentController::class, 'view'])
        ->name('filament.admin.documents.view');
    
    // Certificate download route
    Route::get('/certificates/{submission}/download', function (App\Models\Submission $submission) {
        // Check if user can access this certificate (owner or admin)
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();
        if ($submission->user_id !== $user->id && !$user->hasAnyRole(['admin', 'super_admin'])) {
            abort(403, 'You do not have permission to download this certificate');
        }

        // Check if submission is completed and has certificate
        if ($submission->status !== 'completed' || !$submission->certificate) {
            abort(404, 'Certificate not available');
        }

        // Check if the certificate file exists
        if (!Storage::exists($submission->certificate)) {
            abort(404, 'Certificate file not found');
        }

        // Generate descriptive filename
        $certificateNumber = 'CERT-' . $submission->id;
        $cleanTitle = preg_replace('/[^a-zA-Z0-9\s]/', '', $submission->title);
        $cleanTitle = preg_replace('/\s+/', '_', trim($cleanTitle));
        $cleanTitle = substr($cleanTitle, 0, 50);
        $filename = "Sertifikat_{$certificateNumber}_{$cleanTitle}.pdf";

        return Storage::download($submission->certificate, $filename);
    })->name('certificates.download')->middleware('auth');
});

/*
 * Tracking History Routes
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/tracking/detail', [TrackingHistoryController::class, 'showDetail'])->name('tracking.detail');
    Route::get('/tracking/api/history', [TrackingHistoryController::class, 'getTrackingHistoryJson'])->name('tracking.api.history');
});
