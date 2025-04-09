<x-filament-panels::page>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-medium">
                    {{ $record->title }}
                </h3>
                <p class="text-sm text-gray-500">
                    Type: {{ $record->submissionType->name }} | 
                    Status: <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full 
                    @if ($record->status === 'completed') bg-green-100 text-green-800
                    @elseif ($record->status === 'rejected') bg-red-100 text-red-800
                    @elseif ($record->status === 'in_review') bg-blue-100 text-blue-800
                    @elseif ($record->status === 'revision_needed') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif
                    ">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                </p>
                @if($record->currentStage)
                <p class="text-sm text-gray-500">
                    Current Stage: {{ $record->currentStage->name }}
                </p>
                @endif
            </div>
        </div>

        @if($record->status === 'draft')
            <div class="rounded-md bg-yellow-50 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Submission is in draft state</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>This submission is still in draft status and has not been officially submitted yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
