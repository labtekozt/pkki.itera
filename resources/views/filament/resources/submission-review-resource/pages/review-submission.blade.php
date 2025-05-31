<x-filament::page>
    <form wire:submit.prevent="submitReview">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end items-center">
            <x-filament::button
                type="button"
                color="gray"
                tag="a"
                :href="route('filament.admin.resources.submission-reviews.index')"
            >
                Batal
            </x-filament::button>
        </div>
    </form>

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
                        this.statusLabel = 'Disetujui';
                        this.statusColor = 'emerald';
                        break;
                    case 'rejected':
                        this.statusLabel = 'Ditolak';
                        this.statusColor = 'red';
                        break;
                    case 'revision_needed':
                        this.statusLabel = 'Perlu Revisi';
                        this.statusColor = 'amber';
                        break;
                    default:
                        this.statusLabel = 'Menunggu Peninjauan';
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
                        Konfirmasi Perubahan Status Dokumen
                    </h3>
                    
                    <button type="button" x-on:click="open = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Apakah Anda yakin ingin mengubah status <span x-text="requirementName" class="font-medium"></span> menjadi 
                        <span x-text="statusLabel" x-bind:class="'text-' + statusColor + '-500 font-medium'"></span>?
                    </p>
                </div>
                
                <div class="mb-4">
                    <label for="statusNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Catatan / Alasan
                    </label>
                    <textarea 
                        id="statusNotes"
                        x-model="notes"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        rows="3"
                        placeholder="Tambahkan catatan mengapa Anda mengubah status ini"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <x-filament::button
                        type="button"
                        color="gray"
                        x-on:click="open = false"
                    >
                        Batal
                    </x-filament::button>
                    
                    <x-filament::button
                        type="button"
                        x-bind:color="statusColor === 'emerald' ? 'success' : (statusColor === 'red' ? 'danger' : (statusColor === 'amber' ? 'warning' : 'primary'))"
                        x-on:click="submitStatusChange"
                    >
                        Konfirmasi
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all document status selects
            document.querySelectorAll('[id^="data.document_status_"]').forEach(select => {
                const originalOnChange = select.onchange;
                
                select.onchange = function(e) {
                    // Extract the document ID directly from the select ID
                    // This gets the document ID, not the requirement ID
                    const documentId = select.id.replace('data.document_status_', '');
                    
                    // Validate we have a proper document ID (should be a UUID)
                    if (!documentId || !documentId.match(/^[0-9a-f-]{36}$/i)) {
                        console.warn('Invalid document ID format detected:', documentId);
                        return;
                    }
                    
                    const section = select.closest('section');
                    let requirementName = section ? (section.querySelector('h2')?.textContent || 'Document') : 'Document';
                    requirementName = requirementName.trim();
                    
                    // Get status
                    const status = select.value;
                    
                    // Find associated notes field that matches this document ID
                    let notes = '';
                    const notesFieldId = `data.document_notes_${documentId}`;
                    const notesField = document.getElementById(notesFieldId);
                    if (notesField) {
                        notes = notesField.value || '';
                    }
                    
                    // Store previous value to revert if needed
                    const previousValue = select._previousValue || select.value;
                    
                    // Stop the default change action
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Document status change intercepted:', {
                        documentId,
                        status,
                        notes,
                        requirementName,
                        selectElement: select.id
                    });
                    
                    // Dispatch event to open confirmation modal with the correct document ID
                    window.dispatchEvent(new CustomEvent('open-document-status-modal', {
                        detail: {
                            documentId,
                            status,
                            notes,
                            requirementName
                        }
                    }));
                    
                    // Reset select to its original value to prevent immediate change
                    setTimeout(() => {
                        select.value = previousValue;
                    }, 0);
                };
                
                // Store the current value to track changes
                select.addEventListener('focus', function() {
                    this._previousValue = this.value;
                });
            });
        });
    </script>
</x-filament::page>
