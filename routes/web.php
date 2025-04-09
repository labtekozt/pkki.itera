<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;

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

// Add a 'login' named route that redirects to Filament login
Route::redirect('/login', '/admin/login')->name('login');

// Add document download route for admin panel
Route::get('/admin/documents/{document}/download', function (Document $document) {
    // Check permissions using Auth facade
    abort_unless(Auth::check() && Auth::user()->can('view', $document), 403);
    
    // Get the file path
    $path = storage_path('app/' . $document->uri);
    
    // Check if file exists
    if (!file_exists($path)) {
        abort(404);
    }
    
    // Return the file for download
    return response()->download($path, $document->title . '.' . $document->extension);
})->name('filament.admin.documents.download');
