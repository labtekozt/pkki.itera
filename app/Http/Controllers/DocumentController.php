<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function download(Document $document)
    {
        // Check if user has access to this document
        // This is a simple check, you might want to implement more complex authorization
        if (!Auth::user()) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('public')->exists($document->uri)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $document->uri, 
            $document->title
        );
    }

    public function view(Document $document)
    {
        // Check if user has access to this document
        if (!Auth::user()) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('public')->exists($document->uri)) {
            abort(404, 'File not found');
        }

        $file = Storage::disk('public')->get($document->uri);
        
        // For PDFs and images, we can display them directly in the browser
        // For other file types, we'll download them
        return response($file, 200, [
            'Content-Type' => $document->mimetype,
            'Content-Disposition' => 'inline; filename="' . $document->title . '"',
        ]);
    }
}
