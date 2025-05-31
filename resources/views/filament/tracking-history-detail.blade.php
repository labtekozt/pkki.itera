<x-filament::modal.heading>
    Detail Riwayat Pelacakan
</x-filament::modal.heading>

<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal & Waktu</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-300">{{ $record->created_at->format('d-m-Y H:i:s') }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Kejadian</h3>
            <p class="mt-1 text-sm">
                @php
                    $eventTypes = [
                        'document_uploaded' => ['label' => 'Dokumen Diunggah', 'color' => 'blue'],
                        'document_approved' => ['label' => 'Dokumen Disetujui', 'color' => 'green'],
                        'document_rejected' => ['label' => 'Dokumen Ditolak', 'color' => 'red'],
                        'document_revision_needed' => ['label' => 'Dokumen Perlu Revisi', 'color' => 'yellow'],
                        'stage_transition' => ['label' => 'Tahap Berubah', 'color' => 'purple'],
                        'status_change' => ['label' => 'Status Berubah', 'color' => 'gray'],
                    ];
                    
                    $eventInfo = $eventTypes[$record->event_type] ?? ['label' => $record->event_type, 'color' => 'gray'];
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $eventInfo['color'] }}-100 text-{{ $eventInfo['color'] }}-800">
                    {{ $eventInfo['label'] }}
                </span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Tahap</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-300">{{ $record->stage->name ?? 'Tidak Ada' }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
            <p class="mt-1 text-sm">
                @php
                    $statusTypes = [
                        'started' => ['color' => 'gray'],
                        'in_progress' => ['color' => 'blue'],
                        'approved' => ['color' => 'green'],
                        'rejected' => ['color' => 'red'],
                        'revision_needed' => ['color' => 'yellow'],
                        'objection' => ['color' => 'red'],
                        'completed' => ['color' => 'green'],
                    ];
                    
                    $statusInfo = $statusTypes[$record->status] ?? ['color' => 'gray'];
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusInfo['color'] }}-100 text-{{ $statusInfo['color'] }}-800">
                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                </span>
            </p>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Diproses Oleh</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-300">{{ $record->processor->fullname ?? 'Sistem' }}</p>
    </div>

    @if($record->document_id && $document)
    <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Dokumen Terkait</h3>
        <div class="mt-2 flex items-center space-x-2">
            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $document->title }}</p>
                <p class="text-xs text-gray-500">{{ $document->mimetype }} - {{ $document->human_size }}</p>
            </div>
            <a href="{{ route('filament.admin.documents.download', $document->id) }}" target="_blank" class="ml-auto inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Unduh
            </a>
        </div>
    </div>
    @endif

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Komentar</h3>
        <div class="mt-1 p-2 border rounded-md bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-900 dark:text-gray-300 whitespace-pre-line">{{ $record->comment ?? 'Tidak ada komentar' }}</p>
        </div>
    </div>
</div>