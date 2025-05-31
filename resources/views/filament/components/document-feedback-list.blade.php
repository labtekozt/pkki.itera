{{-- Document Feedback List Component --}}
<div class="space-y-4">
    @foreach($documentsWithFeedback as $docItem)
        @php
            $statusColor = match ($docItem->status) {
                'approved' => 'green',
                'rejected' => 'red',
                'revision_needed' => 'yellow',
                default => 'gray',
            };
            
            $statusIcon = match ($docItem->status) {
                'approved' => '✅',
                'rejected' => '❌',
                'revision_needed' => '⚠️',
                default => '📄',
            };
            
            $statusText = match ($docItem->status) {
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'revision_needed' => 'Perlu Revisi',
                default => 'Tidak Diketahui',
            };
        @endphp
        
        <div class="border border-{{ $statusColor }}-200 bg-{{ $statusColor }}-50 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <div class="text-2xl">{{ $statusIcon }}</div>
                <div class="flex-1">
                    {{-- Document Header --}}
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="font-semibold text-{{ $statusColor }}-800">
                                {{ $docItem->requirement->name ?? 'Dokumen' }}
                            </h4>
                            <p class="text-sm text-{{ $statusColor }}-600">
                                {{ $docItem->document->title }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 rounded-full">
                                {{ $statusText }}
                            </span>
                            <a href="{{ route('filament.admin.documents.download', $docItem->document_id) }}" 
                               target="_blank"
                               class="text-{{ $statusColor }}-600 hover:text-{{ $statusColor }}-800 text-sm font-medium">
                                Unduh
                            </a>
                        </div>
                    </div>
                    
                    {{-- Document Info --}}
                    <div class="text-xs text-{{ $statusColor }}-600 mb-3">
                        {{ $docItem->document->mimetype }} • {{ number_format($docItem->document->size / 1024, 0) }} KB
                        • Diunggah {{ $docItem->created_at->format('d M Y H:i') }}
                    </div>
                    
                    {{-- Reviewer Feedback --}}
                    <div class="bg-white p-3 rounded border border-{{ $statusColor }}-200">
                        <h5 class="font-medium text-gray-900 mb-2 flex items-center">
                            <svg class="h-4 w-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Umpan Balik Peninjau
                        </h5>
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($docItem->notes)) !!}
                        </div>
                    </div>
                    
                    {{-- Action Guidance --}}
                    @if($docItem->status === 'revision_needed')
                    <div class="mt-3 p-3 bg-{{ $statusColor }}-100 rounded border border-{{ $statusColor }}-200">
                        <div class="flex items-start space-x-2">
                            <span class="text-{{ $statusColor }}-500 mt-0.5">💡</span>
                            <div>
                                <p class="text-sm font-medium text-{{ $statusColor }}-800">Tindakan Diperlukan</p>
                                <p class="text-xs text-{{ $statusColor }}-700">
                                    Harap tinjau umpan balik di atas dan unggah versi revisi dari dokumen ini.
                                </p>
                            </div>
                        </div>
                    </div>
                    @elseif($docItem->status === 'rejected')
                    <div class="mt-3 p-3 bg-{{ $statusColor }}-100 rounded border border-{{ $statusColor }}-200">
                        <div class="flex items-start space-x-2">
                            <span class="text-{{ $statusColor }}-500 mt-0.5">🔄</span>
                            <div>
                                <p class="text-sm font-medium text-{{ $statusColor }}-800">Dokumen Ditolak</p>
                                <p class="text-xs text-{{ $statusColor }}-700">
                                    Dokumen ini perlu diganti. Harap unggah versi baru yang mengatasi masalah yang disebutkan dalam umpan balik.
                                </p>
                            </div>
                        </div>
                    </div>
                    @elseif($docItem->status === 'approved')
                    <div class="mt-3 p-3 bg-{{ $statusColor }}-100 rounded border border-{{ $statusColor }}-200">
                        <div class="flex items-start space-x-2">
                            <span class="text-{{ $statusColor }}-500 mt-0.5">✨</span>
                            <div>
                                <p class="text-sm font-medium text-{{ $statusColor }}-800">Dokumen Disetujui</p>
                                <p class="text-xs text-{{ $statusColor }}-700">
                                    Dokumen ini telah disetujui dan memenuhi semua persyaratan. Tidak ada tindakan lebih lanjut yang diperlukan.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    
    {{-- Summary Card --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
        <div class="flex items-start space-x-3">
            <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="font-medium text-blue-800">Apa Selanjutnya?</h4>
                <p class="text-sm text-blue-700 mt-1">
                    @php
                        $hasRejected = $documentsWithFeedback->where('status', 'rejected')->count() > 0;
                        $hasRevisionNeeded = $documentsWithFeedback->where('status', 'revision_needed')->count() > 0;
                        $allApproved = $documentsWithFeedback->where('status', 'approved')->count() === $documentsWithFeedback->count();
                    @endphp
                    
                    @if($allApproved)
                        Semua dokumen Anda telah disetujui! Pengajuan Anda akan berlanjut ke tahap berikutnya.
                    @elseif($hasRejected || $hasRevisionNeeded)
                        Anda perlu mengatasi umpan balik di atas dengan mengunggah dokumen yang telah direvisi. Setelah diperbarui, pengajuan Anda dapat dikirim ulang untuk ditinjau.
                    @else
                        Dokumen Anda sedang dalam peninjauan. Kami akan memberi tahu Anda setelah peninjauan selesai.
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
