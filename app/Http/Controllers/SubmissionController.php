public function storePatent(Request $request)
{
    // Validate the submission data
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'patent_type' => 'required|string',
        'invention_description' => 'required|string',
        'technical_field' => 'nullable|string',
        'background' => 'nullable|string',
        'inventor_details' => 'required|string',
        // Other validation rules
    ]);

    // Start a database transaction to ensure data consistency
    DB::beginTransaction();
    
    try {
        // Find the patent submission type
        $patentType = SubmissionType::where('slug', 'paten')->firstOrFail();
        
        // Find the first workflow stage for patents
        $firstStage = WorkflowStage::where('submission_type_id', $patentType->id)
            ->orderBy('order')
            ->first();
            
        // Create the submission record
        $submission = Submission::create([
            'title' => $validated['title'],
            'submission_type_id' => $patentType->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'draft',
            'user_id' => auth()->id(),
        ]);
        
        // Create the patent-specific details
        $patentDetail = PatentDetail::create([
            'submission_id' => $submission->id,
            'patent_type' => $validated['patent_type'],
            'invention_description' => $validated['invention_description'],
            'technical_field' => $validated['technical_field'],
            'background' => $validated['background'],
            'inventor_details' => $validated['inventor_details'],
        ]);
        
        // Create tracking history for the first stage
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $firstStage->id,
            'status' => 'started',
            'comment' => 'Submission created',
            'processed_by' => auth()->id(),
        ]);
        
        // Process document uploads if any
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $code => $file) {
                // Find the document requirement
                $requirement = DocumentRequirement::where('code', $code)->first();
                
                if ($requirement) {
                    // Store the document
                    $path = $file->store('submissions/' . $submission->id, 'public');
                    
                    // Create document record
                    $document = Document::create([
                        'uri' => $path,
                        'title' => $file->getClientOriginalName(),
                        'mimetype' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                    
                    // Link document to submission
                    SubmissionDocument::create([
                        'submission_id' => $submission->id,
                        'document_id' => $document->id,
                        'requirement_id' => $requirement->id,
                        'status' => 'pending',
                    ]);
                }
            }
        }
        
        DB::commit();
        
        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Patent submission created successfully.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to create submission: ' . $e->getMessage()]);
    }
}

public function show(Submission $submission)
{
    // Eager load relationships to avoid N+1 queries
    $submission->load([
        'submissionType',
        'currentStage',
        'user',
        'submissionDocuments.document',
        'submissionDocuments.requirement',
        'trackingHistory' => function($query) {
            $query->orderBy('created_at', 'desc');
        }
    ]);
    
    // Load the type-specific details based on submission type
    $typeSlug = $submission->submissionType->slug;
    
    switch ($typeSlug) {
        case 'paten':
            $submission->load('patentDetail');
            break;
        case 'brand':
            $submission->load('trademarkDetail');
            break;
        case 'haki':
            $submission->load('copyrightDetail');
            break;
        case 'industrial_design':
            $submission->load('industrialDesignDetail');
            break;
    }
    
    // Get required documents for the current stage
    $requiredDocuments = [];
    if ($submission->currentStage) {
        $requiredDocuments = $submission->currentStage->documentRequirements;
    }
    
    return view('submissions.show', compact('submission', 'requiredDocuments'));
}

            ->orderBy('order')
            ->first();
            
        // Create the submission record
        $submission = Submission::create([
            'title' => $validated['title'],
            'submission_type_id' => $trademarkType->id,
            'current_stage_id' => $firstStage->id,
            'status' => 'draft',
            'user_id' => auth()->id(),
        ]);
        
        // Create the trademark-specific details
        $trademarkDetail = TrademarkDetail::create([
            'submission_id' => $submission->id,
            'trademark_type' => $validated['trademark_type'],
            'description' => $validated['description'],
            'goods_services_description' => $validated['goods_services_description'],
            'nice_classes' => $validated['nice_classes'],
            'has_color_claim' => $validated['has_color_claim'] ?? false,
            'color_description' => $validated['color_description'],
        ]);
        
        // Create tracking history and process documents similar to the patent example
        TrackingHistory::create([
            'submission_id' => $submission->id,
            'stage_id' => $firstStage->id,
            'status' => 'started',
            'comment' => 'Submission created',
            'processed_by' => auth()->id(),
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $code => $file) {
                $requirement = DocumentRequirement::where('code', $code)->first();

                if ($requirement) {
                    $path = $file->store('submissions/' . $submission->id, 'public');

                    $document = Document::create([
                        'uri' => $path,
                        'title' => $file->getClientOriginalName(),
                        'mimetype' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    SubmissionDocument::create([
                        'submission_id' => $submission->id,
                        'document_id' => $document->id,
                        'requirement_id' => $requirement->id,
                        'status' => 'pending',
                    ]);
                }
            }
        }
        
        DB::commit();
        
        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Trademark submission created successfully.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to create submission: ' . $e->getMessage()]);
    }
}
