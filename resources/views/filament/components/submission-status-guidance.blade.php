<div class="p-4 bg-white rounded-lg shadow-sm">
    <div class="space-y-4">
        <!-- Status Information with Large Clear Icons -->
        <div class="flex items-center">
            @php
                $statusColor = match ($status) {
                    'draft' => 'gray',
                    'submitted' => 'blue',
                    'in_review' => 'amber',
                    'revision_needed' => 'red',
                    'approved' => 'emerald',
                    'rejected' => 'red',
                    'completed' => 'green',
                    'cancelled' => 'gray',
                    default => 'gray',
                };
                
                $statusLabel = match ($status) {
                    'draft' => 'Draft',
                    'submitted' => 'Dikirim',
                    'in_review' => 'Dalam Peninjauan',
                    'revision_needed' => 'Perlu Revisi',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    default => 'Tidak Diketahui',
                };
                
                $statusIcon = match ($status) {
                    'draft' => 'heroicon-o-pencil',
                    'submitted' => 'heroicon-o-paper-airplane',
                    'in_review' => 'heroicon-o-document-magnifying-glass',
                    'revision_needed' => 'heroicon-o-document-minus',
                    'approved' => 'heroicon-o-check-badge',
                    'rejected' => 'heroicon-o-x-circle',
                    'completed' => 'heroicon-o-trophy',
                    'cancelled' => 'heroicon-o-x-mark',
                    default => 'heroicon-o-question-mark-circle',
                };
            @endphp

            <div class="w-16 h-16 flex items-center justify-center rounded-full bg-{{ $statusColor }}-100 mr-4">
                <x-dynamic-component
                    component="heroicon-o-{{ str_replace('heroicon-o-', '', $statusIcon) }}"
                    class="w-10 h-10 text-{{ $statusColor }}-600"
                />
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Status Saat Ini: {{ $statusLabel }}</h2>
                <p class="text-base text-gray-600">
                    {{ match ($status) {
                        'draft' => 'Pengajuan Anda masih dapat diedit. Belum dikirim untuk ditinjau.',
                        'submitted' => 'Pengajuan Anda telah berhasil dikirim untuk ditinjau.',
                        'in_review' => 'Pengajuan Anda sedang ditinjau oleh tim kami.',
                        'revision_needed' => 'Pengajuan Anda memerlukan beberapa perubahan sebelum dapat disetujui.',
                        'approved' => 'Selamat! Pengajuan Anda telah disetujui.',
                        'rejected' => 'Maaf, pengajuan Anda tidak disetujui.',
                        'completed' => 'Pengajuan Anda telah selesai dan telah disertifikasi.',
                        'cancelled' => 'Pengajuan ini telah dibatalkan.',
                        default => 'Informasi status tidak tersedia.',
                    } }}
                </p>
            </div>
        </div>

        <!-- Next Steps Section with Checkboxes -->
        @if (count($nextSteps) > 0)
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Apa yang Harus Dilakukan Selanjutnya:</h3>
                <div class="pl-1 space-y-4">
                    @foreach ($nextSteps as $step)
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                @if ($step['done'])
                                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-base font-medium text-gray-900">{{ $step['step'] }}</p>
                                <p class="text-sm text-gray-600">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Action Button -->
        @if ($status === 'draft')
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex">
                    <a href="{{ route('filament.admin.resources.submissions.edit', $submission) }}" 
                       class="inline-flex items-center px-6 py-3 text-lg font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Lengkapi Pengajuan Anda
                    </a>
                </div>
                
                @if (!$documentComplete)
                    <p class="mt-3 text-sm text-red-600">
                        <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Anda perlu mengunggah semua dokumen yang diperlukan sebelum mengirim
                    </p>
                @endif
            </div>
        @elseif ($status === 'revision_needed')
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex">
                    <a href="{{ route('filament.admin.resources.submissions.edit', $submission) }}" 
                       class="inline-flex items-center px-6 py-3 text-lg font-medium rounded-md shadow-sm text-white bg-warning-600 hover:bg-warning-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warning-500">
                        <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Perbarui & Kirim Ulang
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>