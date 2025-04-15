<x-filament-panels::page>
    {{-- Submission header information --}}
    <div class="mb-6 bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
            <div>
                <h2 class="text-2xl font-bold text-primary-600">{{ $record->title }}</h2>
                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <div class="text-gray-600">
                        <span class="font-medium">Type:</span> {{ $record->submissionType->name }}
                    </div>
                    <div class="text-gray-600">
                        <span class="font-medium">Stage:</span> {{ $record->currentStage->name ?? 'Not assigned' }}
                    </div>
                    <div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium 
                            @if($record->status === 'approved') bg-success-100 text-success-700
                            @elseif($record->status === 'rejected') bg-danger-100 text-danger-700
                            @elseif($record->status === 'revision_needed') bg-warning-100 text-warning-700 
                            @elseif($record->status === 'in_review') bg-primary-100 text-primary-700
                            @elseif($record->status === 'draft') bg-gray-100 text-gray-700
                            @elseif($record->status === 'submitted') bg-info-100 text-info-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                        </span>
                    </div>
                </div>
                <div class="mt-3 text-gray-600">
                    <span class="font-medium">Submitted by:</span> {{ $record->user->fullname }}
                </div>
                <div class="mt-1 text-gray-600">
                    <span class="font-medium">Submitted on:</span> {{ $record->created_at->format('M d, Y H:i') }}
                </div>
                
                @if($revisionCount > 0)
                    <div class="mt-3">
                        <span class="px-3 py-1 rounded-full text-sm font-medium 
                            @if($revisionCount >= 3) bg-danger-100 text-danger-700 @else bg-warning-100 text-warning-700 @endif">
                            Revision count: {{ $revisionCount }}/3
                        </span>
                        @if($revisionCount >= 3 && !$record->metadata['revision_override'] ?? false)
                            <span class="text-sm text-danger-600 ml-2">
                                <i class="fas fa-lock mr-1"></i> Locked (requires admin override)
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            
            <div>
                @if($assignment)
                    <div class="px-4 py-3 bg-primary-50 border border-primary-200 rounded-lg">
                        <div class="text-sm text-primary-700">
                            <span class="font-medium">Your assignment:</span> 
                            {{ $assignment->status === 'pending' ? 'Pending review' : 'In progress' }}
                        </div>
                        <div class="text-sm text-primary-700 mt-1">
                            <span class="font-medium">Assigned on:</span> 
                            {{ $assignment->assigned_at->format('M d, Y') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs for documents, reviews, and history --}}
    <x-filament::tabs>
        <x-filament::tabs.item label="Submission Details" icon="heroicon-o-document-text" active>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Submission documents section --}}
                <div class="col-span-2 bg-white rounded-xl shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Submission Documents</h3>
                        
                        @if($record->submissionDocuments->isNotEmpty())
                            <div class="divide-y">
                                @foreach($record->submissionDocuments as $document)
                                    <div class="py-4 flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-800">
                                                {{ $document->requirement->name ?? 'Unnamed Document' }}
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                {{ $document->document->title ?? 'No title' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 py-1 rounded text-xs font-medium
                                                @if($document->status === 'approved') bg-success-100 text-success-700
                                                @elseif($document->status === 'rejected') bg-danger-100 text-danger-700
                                                @elseif($document->status === 'pending') bg-gray-100 text-gray-700
                                                @else bg-gray-100 text-gray-700
                                                @endif">
                                                {{ ucfirst($document->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-8 text-gray-500 text-center">
                                No documents have been uploaded for this submission.
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Reviewer notes and comments section --}}
                <div class="col-span-1 bg-white rounded-xl shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Review Notes</h3>
                        
                        <form wire:submit.prevent="saveNotes" class="mb-6">
                            <div class="mb-4">
                                <textarea wire:model="reviewNotes" rows="4" class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-30" placeholder="Add private notes about this submission..."></textarea>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                Save Notes
                            </button>
                        </form>
                        
                        <div class="border-t pt-4">
                            <h4 class="font-medium mb-3 text-gray-800">Stage Requirements</h4>
                            @if($record->currentStage?->documentRequirements->isNotEmpty())
                                <ul class="space-y-3">
                                    @foreach($record->currentStage->documentRequirements as $requirement)
                                        <li class="flex items-center">
                                            <span class="mr-2">
                                                @php
                                                    $fulfilled = $record->submissionDocuments
                                                        ->where('requirement_id', $requirement->id)
                                                        ->where('status', 'approved')
                                                        ->isNotEmpty();
                                                @endphp
                                                
                                                @if($fulfilled)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-danger-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </span>
                                            <span class="{{ $fulfilled ? 'text-success-700' : 'text-danger-700' }}">
                                                {{ $requirement->name }}
                                                @if($requirement->pivot->is_required)
                                                    <span class="text-danger-500 text-xs ml-1">(Required)</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-gray-500 text-sm py-2">
                                    No requirements defined for this stage.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::tabs.item>
        
        <x-filament::tabs.item label="Review History" icon="heroicon-o-clock">
            <div class="mt-4 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Review History</h3>
                
                @if($record->trackingHistory->isNotEmpty())
                    <div class="space-y-6">
                        @foreach($record->trackingHistory()->with(['stage', 'processor'])->orderBy('created_at', 'desc')->get() as $history)
                            <div class="border-l-4 
                                @if($history->action === 'approve') border-success-500
                                @elseif($history->action === 'reject') border-danger-500
                                @elseif($history->action === 'request_revision') border-warning-500
                                @elseif($history->action === 'advance_stage') border-info-500
                                @elseif($history->action === 'admin_override') border-purple-500
                                @else border-gray-300
                                @endif
                                pl-4 py-3">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-start gap-2">
                                    <div>
                                        <div class="font-medium text-gray-800">
                                            @if($history->action === 'approve')
                                                Approved
                                            @elseif($history->action === 'reject')
                                                Rejected
                                            @elseif($history->action === 'request_revision')
                                                Revision Requested
                                            @elseif($history->action === 'advance_stage')
                                                Advanced to Next Stage
                                            @elseif($history->action === 'admin_override')
                                                Admin Override
                                            @elseif($history->action === 'complete')
                                                Completed
                                            @else
                                                {{ ucfirst(str_replace('_', ' ', $history->action)) }}
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ $history->stage->name ?? 'Unknown Stage' }} 
                                            @if($history->previous_stage_id)
                                                from {{ optional($history->previousStage)->name ?? 'previous stage' }}
                                            @endif
                                        </div>
                                        @if($history->comment)
                                            <div class="mt-3 text-sm bg-gray-50 p-3 rounded-md">
                                                {{ $history->comment }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right sm:min-w-[120px] text-gray-500 text-sm">
                                        <div>
                                            {{ $history->created_at->format('M d, Y H:i') }}
                                        </div>
                                        <div class="mt-1">
                                            by {{ optional($history->processor)->fullname ?? 'System' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-gray-500 text-center">
                        No review history available for this submission.
                    </div>
                @endif
            </div>
        </x-filament::tabs.item>
        
        <x-filament::tabs.item label="Reviewer Assignment" icon="heroicon-o-user-group">
            <div class="mt-4 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Reviewer Assignments</h3>
                
                @if(auth()->user()->hasRole(['admin', 'super_admin']))
                    <div class="mb-6">
                        <form wire:submit.prevent="assignReviewer" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select
                                        wire:model="newReviewerId"
                                        placeholder="Select a reviewer"
                                        class="w-full rounded-lg"
                                    >
                                        <option value="">-- Select Reviewer --</option>
                                        @foreach(\App\Models\User::role('admin')->get() as $reviewer)
                                            <option value="{{ $reviewer->id }}">{{ $reviewer->fullname }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                            <div>
                                <x-filament::button type="submit" class="w-full">
                                    Assign Reviewer
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                @endif
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned By</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($record->reviewerAssignments()->with(['reviewer', 'assigned_by_user', 'stage'])->orderBy('created_at', 'desc')->get() as $assignment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $assignment->reviewer->fullname ?? 'Unknown User' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $assignment->stage->name ?? 'Unknown Stage' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $assignment->assigned_by_user->fullname ?? 'System' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($assignment->status === 'completed') bg-success-100 text-success-700
                                            @elseif($assignment->status === 'in_progress') bg-primary-100 text-primary-700
                                            @else bg-gray-100 text-gray-700
                                            @endif">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $assignment->assigned_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $assignment->completed_at ? $assignment->completed_at->format('M d, Y') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No reviewer assignments found for this submission.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::tabs.item>
    </x-filament::tabs>
</x-filament-panels::page>
