@extends('layouts.app')

@section('title', 'Tracking History Details')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-4">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Tracking History Details
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Complete audit trail of all submission activities and document changes.
            </p>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Filters</h3>
            <form action="{{ route('tracking.detail') }}" method="GET" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Submission Filter -->
                    <div>
                        <label for="submission_id" class="block text-sm font-medium text-gray-700">Submission</label>
                        <select id="submission_id" name="submission_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">All Submissions</option>
                            @foreach($submissionOptions as $submission)
                                <option value="{{ $submission->id }}" {{ $submissionId == $submission->id ? 'selected' : '' }}>
                                    {{ $submission->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Document Filter -->
                    <div>
                        <label for="document_id" class="block text-sm font-medium text-gray-700">Document</label>
                        <select id="document_id" name="document_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">All Documents</option>
                            @foreach($documentOptions as $document)
                                <option value="{{ $document->id }}" {{ $documentId == $document->id ? 'selected' : '' }}>
                                    {{ $document->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                        <select id="user_id" name="user_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">All Users</option>
                            @foreach($userOptions as $user)
                                <option value="{{ $user->id }}" {{ isset($userId) && $userId == $user->id ? 'selected' : '' }}>
                                    {{ $user->fullname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Range Filters -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Event Types</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($eventTypeOptions as $type)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="event_type_{{ $type }}" name="event_types[]" type="checkbox" value="{{ $type }}" 
                                        {{ in_array($type, $eventTypes) ? 'checked' : '' }}
                                        class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="event_type_{{ $type }}" class="font-medium text-gray-700">
                                        {{ str_replace('_', ' ', ucfirst($type)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Status</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($statusOptions as $status)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="status_{{ $status }}" name="statuses[]" type="checkbox" value="{{ $status }}" 
                                        {{ in_array($status, $statuses) ? 'checked' : '' }}
                                        class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="status_{{ $status }}" class="font-medium text-gray-700">
                                        {{ str_replace('_', ' ', ucfirst($status)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Event
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Submission
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stage
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trackingHistories as $history)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $history->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if(str_contains($history->event_type ?? '', 'approved')) bg-green-100 text-green-800
                                    @elseif(str_contains($history->event_type ?? '', 'rejected')) bg-red-100 text-red-800
                                    @elseif(str_contains($history->event_type ?? '', 'revision')) bg-yellow-100 text-yellow-800
                                    @elseif(str_contains($history->event_type ?? '', 'document')) bg-blue-100 text-blue-800
                                    @elseif(str_contains($history->event_type ?? '', 'stage')) bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ str_replace('_', ' ', ucfirst($history->event_type ?? $history->action)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($history->submission)
                                    <a href="{{ route('tracking.timeline', $history->submission->id) }}" class="text-primary-600 hover:text-primary-900">
                                        {{ $history->submission->title }}
                                        <span class="text-xs text-gray-500">({{ $history->submission->submissionType->name ?? 'Unknown' }})</span>
                                    </a>
                                @else
                                    <span class="text-gray-500">Unknown</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $history->stage->name ?? 'Unknown Stage' }}
                                @if($history->previous_stage_id)
                                    <span class="text-xs">
                                        (from {{ $history->previousStage->name ?? 'Unknown' }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($history->status === 'approved') bg-green-100 text-green-800
                                    @elseif($history->status === 'rejected') bg-red-100 text-red-800
                                    @elseif($history->status === 'revision_needed') bg-yellow-100 text-yellow-800
                                    @elseif($history->status === 'completed') bg-green-100 text-green-800
                                    @elseif($history->status === 'in_progress') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ str_replace('_', ' ', ucfirst($history->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $history->processor->fullname ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-600">
                                <button type="button" onclick="toggleDetails('{{ $history->id }}')" class="text-primary-600 hover:text-primary-900">
                                    Details
                                </button>
                            </td>
                        </tr>
                        <!-- Expandable Details Row -->
                        <tr id="details-{{ $history->id }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Comment Section -->
                                    @if($history->comment)
                                        <div class="col-span-1 md:col-span-2">
                                            <h4 class="text-sm font-medium text-gray-700">Comment</h4>
                                            <p class="mt-1 text-sm text-gray-900">{{ $history->comment }}</p>
                                        </div>
                                    @endif
                                    
                                    <!-- Status Transition -->
                                    @if($history->source_status && $history->target_status)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Status Transition</h4>
                                            <p class="mt-1 text-sm text-gray-900">
                                                <span class="@if($history->source_status === 'approved') text-green-600 
                                                          @elseif($history->source_status === 'rejected') text-red-600 
                                                          @elseif($history->source_status === 'revision_needed') text-yellow-600 
                                                          @else text-gray-600 @endif">
                                                    {{ str_replace('_', ' ', ucfirst($history->source_status)) }}
                                                </span>
                                                <svg class="inline-block h-4 w-4 text-gray-400 mx-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="@if($history->target_status === 'approved') text-green-600 
                                                          @elseif($history->target_status === 'rejected') text-red-600 
                                                          @elseif($history->target_status === 'revision_needed') text-yellow-600 
                                                          @else text-gray-600 @endif">
                                                    {{ str_replace('_', ' ', ucfirst($history->target_status)) }}
                                                </span>
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <!-- Document Information -->
                                    @if($history->document)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Document</h4>
                                            <p class="mt-1 text-sm text-gray-900">
                                                <a href="{{ asset('storage/' . $history->document->uri) }}" target="_blank" class="text-primary-600 hover:text-primary-900">
                                                    {{ $history->document->title }}
                                                </a>
                                                <span class="text-xs text-gray-500">
                                                    ({{ $history->document->mimetype }}, {{ number_format($history->document->size / 1024, 1) }} KB)
                                                </span>
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <!-- Metadata (if present) -->
                                    @if($history->metadata)
                                        <div class="col-span-1 md:col-span-2">
                                            <h4 class="text-sm font-medium text-gray-700">Additional Details</h4>
                                            <div class="mt-1 text-sm text-gray-900 bg-gray-100 p-3 rounded">
                                                <pre class="whitespace-pre-wrap">{{ json_encode($history->metadata, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Resolution Date -->
                                    @if($history->resolved_at)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Resolved On</h4>
                                            <p class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($history->resolved_at)->format('Y-m-d H:i:s') }}</p>
                                        </div>
                                    @endif
                                    
                                    <!-- Record IDs -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Reference IDs</h4>
                                        <div class="mt-1 grid grid-cols-2 gap-1 text-xs text-gray-500">
                                            <div>Record ID:</div>
                                            <div class="font-mono">{{ $history->id }}</div>
                                            
                                            <div>Submission ID:</div>
                                            <div class="font-mono">{{ $history->submission_id }}</div>
                                            
                                            @if($history->document_id)
                                                <div>Document ID:</div>
                                                <div class="font-mono">{{ $history->document_id }}</div>
                                            @endif
                                            
                                            <div>Stage ID:</div>
                                            <div class="font-mono">{{ $history->stage_id }}</div>
                                            
                                            @if($history->previous_stage_id)
                                                <div>Previous Stage ID:</div>
                                                <div class="font-mono">{{ $history->previous_stage_id }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No tracking records found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
            {{ $trackingHistories->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDetails(id) {
        const detailsRow = document.getElementById(`details-${id}`);
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
        } else {
            detailsRow.classList.add('hidden');
        }
    }
</script>
@endpush