<x-filament-panels::page>
    <x-filament::section>
        <div class="mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-lg font-semibold mb-1">{{ $record->title }}</h2>
                    <p class="text-sm text-gray-500">
                        Type: {{ $record->submissionType->name }}
                        | Status: 
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-gray-100 text-gray-800' => $record->status === 'draft',
                            'bg-blue-100 text-blue-800' => $record->status === 'submitted',
                            'bg-yellow-100 text-yellow-800' => $record->status === 'in_review',
                            'bg-red-100 text-red-800' => in_array($record->status, ['rejected', 'revision_needed']),
                            'bg-green-100 text-green-800' => in_array($record->status, ['approved', 'completed']),
                        ])>
                            {{ ucfirst($record->status) }}
                        </span>
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
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
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
    
    <!-- Document Status Update Modal -->
    <div id="documentStatusModal" x-data="{
        open: false,
        documentId: null,
        status: null,
        statusLabel: '',
        statusColor: '',
        notes: '',
        requirementName: '',
        
        init() {
            const modal = this;
            window.addEventListener('open-document-status-modal', event => {
                this.documentId = event.detail.documentId;
                this.status = event.detail.status;
                this.notes = event.detail.notes || '';
                this.requirementName = event.detail.requirementName || 'Document';
                
                // Set color and label based on status
                switch (this.status) {
                    case 'approved':
                        this.statusLabel = 'Approved';
                        this.statusColor = 'emerald';
                        break;
                    case 'rejected':
                        this.statusLabel = 'Rejected';
                        this.statusColor = 'red';
                        break;
                    case 'revision_needed':
                        this.statusLabel = 'Revision Needed';
                        this.statusColor = 'amber';
                        break;
                    default:
                        this.statusLabel = 'Pending Review';
                        this.statusColor = 'gray';
                }
                
                this.open = true;
            });
        },
        
        submitStatusChange() {
            @this.updateDocumentStatus(this.documentId, this.status, this.notes);
            this.open = false;
        }
    }">
        <!-- Modal overlay -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-gray-500 bg-opacity-75"
        ></div>
        
        <!-- Modal content -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0"
            x-cloak
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Confirm Document Status Change
                    </h3>
                    
                    <button type="button" x-on:click="open = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to change the status of <span x-text="requirementName" class="font-medium"></span> to 
                        <span x-text="statusLabel" x-bind:class="'text-' + statusColor + '-500 font-medium'"></span>?
                    </p>
                </div>
                
                <div class="mb-4">
                    <label for="statusNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notes / Reason
                    </label>
                    <textarea 
                        id="statusNotes"
                        x-model="notes"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        rows="3"
                        placeholder="Add notes about why you're changing the status (optional)"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <x-filament::button
                        type="button"
                        color="gray"
                        x-on:click="open = false"
                    >
                        Cancel
                    </x-filament::button>
                    
                    <x-filament::button
                        type="button"
                        x-bind:color="statusColor === 'emerald' ? 'success' : (statusColor === 'red' ? 'danger' : (statusColor === 'amber' ? 'warning' : 'primary'))"
                        x-on:click="submitStatusChange"
                    >
                        Confirm
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Intercept status changes in the table edit form
            const handleTableEdit = function() {
                // Monitor for Filament edit forms that might appear
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            mutation.addedNodes.forEach(function(node) {
                                // Check if this is a modal with status field
                                if (node.querySelector && node.querySelector('select[name="data.status"]')) {
                                    const statusSelect = node.querySelector('select[name="data.status"]');
                                    const notesField = node.querySelector('textarea[name="data.notes"]');
                                    const submitBtn = node.querySelector('button[type="submit"]');
                                    
                                    if (statusSelect && submitBtn) {
                                        // Store original handler
                                        const originalSubmitHandler = submitBtn.onclick;
                                        
                                        // Replace with our handler
                                        submitBtn.onclick = function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            
                                            // Extract submission document ID using multiple methods for reliability
                                            let documentId = null;
                                            
                                            // Method 1: Try to get from hidden record ID field
                                            const recordIdField = node.querySelector('input[name="record"]');
                                            if (recordIdField && recordIdField.value) {
                                                documentId = recordIdField.value;
                                            }
                                            
                                            // Method 2: Try to extract from form action URL - most reliable
                                            if (!documentId) {
                                                const formAction = node.querySelector('form')?.action || '';
                                                const urlMatches = formAction.match(/\/([0-9a-f-]{36})(\/edit)?$/i);
                                                if (urlMatches && urlMatches[1]) {
                                                    documentId = urlMatches[1];
                                                }
                                            }
                                            
                                            // Method 3: Find record ID in any data-* attribute
                                            if (!documentId) {
                                                const dataAttributes = node.querySelectorAll('[data-id]');
                                                if (dataAttributes.length > 0) {
                                                    documentId = dataAttributes[0].dataset.id;
                                                }
                                            }
                                            
                                            // Log additional info for debugging
                                            console.log('Form action URL:', node.querySelector('form')?.action);
                                            console.log('Available data-* elements:', node.querySelectorAll('[data-*]'));
                                            
                                            // If we still can't extract ID, fallback to original behavior
                                            if (!documentId) {
                                                console.warn('Could not extract document ID, falling back to original behavior');
                                                if (originalSubmitHandler) originalSubmitHandler(e);
                                                return;
                                            }
                                            
                                            // Get document title or requirement name for better context
                                            let requirementName = 'this document';
                                            const modalTitle = node.querySelector('.fi-modal-heading');
                                            if (modalTitle && modalTitle.textContent) {
                                                requirementName = modalTitle.textContent.includes('Edit') ? 
                                                    modalTitle.textContent.replace('Edit', '').trim() : 
                                                    'this document';
                                            }
                                            
                                            // Close the modal
                                            const closeBtn = node.querySelector('button[x-on\\:click="close"]');
                                            if (closeBtn) closeBtn.click();
                                            
                                            // Open our confirmation modal
                                            window.dispatchEvent(new CustomEvent('open-document-status-modal', {
                                                detail: {
                                                    documentId,
                                                    status: statusSelect.value,
                                                    notes: notesField ? notesField.value : '',
                                                    requirementName
                                                }
                                            }));
                                        };
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Start observing the entire document
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            };
            
            // Initialize the handler
            handleTableEdit();
        });
    </script>
</x-filament-panels::page>
