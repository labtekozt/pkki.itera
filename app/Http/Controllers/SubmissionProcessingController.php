public function processSubmission(Request $request, Submission $submission)
{
    $validated = $request->validate([
        'status' => 'required|in:in_progress,approved,rejected,revision_needed,completed',
        'comment' => 'required|string',
        'document' => 'nullable|file|max:10240', // Optional attachment
        'next_stage' => 'nullable|exists:workflow_stages,id',
    ]);
    
    DB::beginTransaction();
    
    try {
        // Update submission status
        $submission->status = match($validated['status']) {
            'approved' => $validated['next_stage'] ? 'in_review' : 'approved',
            'completed' => 'completed',
            'rejected' => 'rejected',
            'revision_needed' => 'revision_needed',
            default => 'in_review',
        };
        
        // Store document if uploaded
        $documentId = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('submissions/' . $submission->id . '/processing', 'public');
            
            $document = Document::create([
                'uri' => $path,
                'title' => $file->getClientOriginalName(),
                'mimetype' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            
            $documentId = $document->id;
        }
        
        // Create tracking history for current stage
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'status' => $validated['status'],
            'comment' => $validated['comment'],
            'document_id' => $documentId,
            'processed_by' => auth()->id(),
        ]);
        
        // If moving to next stage
        if ($validated['status'] === 'approved' && $validated['next_stage']) {
            // Update current stage
            $submission->current_stage_id = $validated['next_stage'];
            
            // Create tracking for new stage
            TrackingHistory::create([
                'submission_id' => $submission->id,
                'stage_id' => $validated['next_stage'],
                'status' => 'started',
                'comment' => 'Stage has been initiated',
                'processed_by' => auth()->id(),
            ]);
        }
        
        $submission->save();
        
        DB::commit();
        
        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Submission processed successfully.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to process submission: ' . $e->getMessage()]);
    }
}
