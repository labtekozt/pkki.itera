<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Models\Document;
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
});

/*
 * Tracking History Routes
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/tracking/detail', [TrackingHistoryController::class, 'showDetail'])->name('tracking.detail');
    Route::get('/tracking/api/history', [TrackingHistoryController::class, 'getTrackingHistoryJson'])->name('tracking.api.history');
});
