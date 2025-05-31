<div class="p-4 bg-white rounded-lg shadow-sm">
    @if($documentComplete)
        <!-- All documents are complete -->
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Semua Dokumen Wajib Lengkap</h3>
                <p class="text-base text-gray-700 mt-1">
                    Anda telah mengunggah semua dokumen yang diperlukan untuk pengajuan Anda.
                    @if($submission->status === 'draft')
                        Sekarang Anda dapat mengirim aplikasi untuk ditinjau.
                    @endif
                </p>
            </div>
        </div>
    @else
        <!-- Missing documents -->
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Dokumen Wajib Belum Lengkap</h3>
                    <p class="text-base text-gray-700 mt-1">
                        Pengajuan Anda masih kekurangan dokumen yang diperlukan.
                        @if($submission->status === 'draft')
                            Anda perlu mengunggah semua dokumen yang diperlukan sebelum dapat mengirim.
                        @else
                            Silakan unggah dokumen-dokumen ini untuk melengkapi pengajuan Anda.
                        @endif
                    </p>
                </div>
            </div>
            
            <!-- List of missing documents -->
            <div class="mt-6">
                <h4 class="text-lg font-medium text-gray-800">Dokumen yang Perlu Anda Unggah:</h4>
                <div class="mt-3 pl-3">
                    <ul class="list-disc space-y-3 pl-5 text-base">
                        @foreach($missingDocuments as $document)
                            <li>
                                <span class="font-medium text-red-700">{{ $document['name'] }}</span>
                                @if(isset($document['description']) && !empty($document['description']))
                                    <p class="text-gray-600 mt-1 text-sm">{{ $document['description'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
            <!-- Action button to upload documents -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('filament.admin.resources.submissions.edit', $submission) }}" 
                   class="inline-flex items-center px-6 py-3 text-lg font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Unggah Dokumen yang Kurang
                </a>
            </div>
        </div>
    @endif
</div>