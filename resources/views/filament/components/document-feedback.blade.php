@php
    use Illuminate\Support\Str;
    
    // Define status-specific styles
    $statusStyles = [
        'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-800 text-yellow-700 text-yellow-600',
        'approved' => 'bg-green-50 border-green-200 text-green-800 text-green-700 text-green-600',
        'rejected' => 'bg-red-50 border-red-200 text-red-800 text-red-700 text-red-600',
        'revision_needed' => 'bg-orange-50 border-orange-200 text-orange-800 text-orange-700 text-orange-600',
        'default' => 'bg-gray-50 border-gray-200 text-gray-800 text-gray-700 text-gray-600'
    ];
    
    $currentStatus = $record->status ?? 'default';
    $styles = $statusStyles[$currentStatus] ?? $statusStyles['default'];
    $styleArray = explode(' ', $styles);
@endphp

<div class="space-y-6">
    {{-- Status Overview Section --}}
    <div class="{{ $styleArray[0] }} border {{ $styleArray[1] }} rounded-lg p-4">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                @if($statusInfo['icon'])
                    <x-heroicon-o-{{ str_replace('heroicon-o-', '', $statusInfo['icon']) }} class="h-6 w-6 {{ $styleArray[4] }}" />
                @endif
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium {{ $styleArray[2] }}">
                    Status Dokumen: {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                </h3>
                <p class="mt-1 text-sm {{ $styleArray[3] }}">
                    {{ $statusInfo['message'] }}
                </p>
            </div>
        </div>
    </div>

    {{-- Document Information --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Informasi Dokumen</h4>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Jenis Dokumen</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $record->requirement->name ?? 'Tidak Diketahui' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama File</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $record->document->title ?? 'Tidak Diketahui' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Diunggah</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $record->created_at->format('d M Y H:i') }}</dd>
            </div>
            @if($reviewedAt && $record->status !== 'pending')
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Terakhir Ditinjau</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $reviewedAt->format('d M Y H:i') }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Reviewer Feedback Section --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
            @svg('heroicon-o-chat-bubble-left-right', 'h-4 w-4 mr-2')
            Umpan Balik Peninjau
        </h4>
        
        @if($notes && $notes !== 'No specific feedback provided.')
            <div class="prose prose-sm max-w-none">
                <div class="bg-gray-50 rounded-md p-3 border-l-4 border-{{ $statusInfo['color'] }}-400">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $notes }}</p>
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <div class="text-gray-400 mb-2">
                    @svg('heroicon-o-chat-bubble-left-ellipsis', 'h-8 w-8 mx-auto')
                </div>
                <p class="text-sm text-gray-500">
                    @if($record->status === 'pending')
                        Dokumen Anda sedang menunggu peninjauan. Umpan balik akan muncul di sini setelah ditinjau.
                    @elseif($record->status === 'approved')
                        Dokumen disetujui tanpa umpan balik khusus.
                    @else
                        Tidak ada umpan balik khusus yang diberikan oleh peninjau.
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- Action Items Section --}}
    @if(in_array($record->status, ['rejected', 'revision_needed']))
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-yellow-800 mb-2 flex items-center">
            @svg('heroicon-o-light-bulb', 'h-4 w-4 mr-2')
            Langkah Selanjutnya
        </h4>
        <ul class="text-sm text-yellow-700 space-y-1">
            @if($record->status === 'rejected')
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-1.5 h-1.5 bg-yellow-600 rounded-full mt-2 mr-2"></span>
                    Unggah dokumen baru yang telah diperbaiki
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-1.5 h-1.5 bg-yellow-600 rounded-full mt-2 mr-2"></span>
                    Tangani semua masalah yang disebutkan dalam umpan balik
                </li>
            @else
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-1.5 h-1.5 bg-yellow-600 rounded-full mt-2 mr-2"></span>
                    Revisi dokumen Anda berdasarkan umpan balik di atas
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-1.5 h-1.5 bg-yellow-600 rounded-full mt-2 mr-2"></span>
                    Unggah versi yang telah direvisi
                </li>
            @endif
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-yellow-600 rounded-full mt-2 mr-2"></span>
                Hubungi dukungan jika Anda memerlukan klarifikasi
            </li>
        </ul>
    </div>
    @elseif($record->status === 'approved')
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-green-800 mb-2 flex items-center">
            @svg('heroicon-o-check-circle', 'h-4 w-4 mr-2')
            Selamat!
        </h4>
        <p class="text-sm text-green-700">
            Dokumen Anda telah disetujui dan memenuhi semua persyaratan. Tidak ada tindakan lebih lanjut yang diperlukan untuk dokumen ini.
        </p>
    </div>
    @endif
</div>
