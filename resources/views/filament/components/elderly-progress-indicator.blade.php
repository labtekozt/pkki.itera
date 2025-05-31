{{-- Mobile-Optimized Progress Indicator for Elderly Users --}}
<div class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 sm:p-6 rounded-xl border border-blue-200">
    {{-- Mobile Progress Bar (visible on small screens) --}}
    <div class="block sm:hidden mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-lg font-semibold text-blue-800">Langkah {{ $currentStep }} dari {{ $totalSteps }}</span>
            <span class="text-sm text-blue-600">{{ round(($currentStep / $totalSteps) * 100) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-blue-500 h-3 rounded-full transition-all duration-300" 
                 style="width: {{ round(($currentStep / $totalSteps) * 100) }}%"></div>
        </div>
        
        {{-- Current step info for mobile --}}
        <div class="mt-3 p-3 bg-white rounded-lg border border-blue-200">
            @php
                $stepTitles = [
                    1 => ['title' => 'Informasi Dasar', 'icon' => '📝', 'desc' => 'Isi informasi dasar pengajuan'],
                    2 => ['title' => 'Upload Dokumen', 'icon' => '📎', 'desc' => 'Upload semua dokumen yang diperlukan'],
                    3 => ['title' => 'Review & Kirim', 'icon' => '✅', 'desc' => 'Review dan kirim pengajuan']
                ];
                $current = $stepTitles[$currentStep];
            @endphp
            
            <div class="flex items-center space-x-3">
                <span class="text-3xl">{{ $current['icon'] }}</span>
                <div>
                    <div class="font-semibold text-gray-900">{{ $current['title'] }}</div>
                    <div class="text-sm text-gray-600">{{ $current['desc'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop Progress Steps (hidden on small screens) --}}
    <div class="hidden sm:block">
        <div class="text-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">📝 Progress Pengajuan Anda</h3>
            <p class="text-sm text-gray-600">Langkah {{ $currentStep }} dari {{ $totalSteps }}</p>
        </div>

        <div class="flex justify-between items-center space-x-2">
            @php
                $steps = [
                    1 => ['title' => 'Informasi Dasar', 'icon' => '📝', 'completed' => true],
                    2 => ['title' => 'Upload Dokumen', 'icon' => '📎', 'completed' => $isDocumentComplete],
                    3 => ['title' => 'Review & Kirim', 'icon' => '✅', 'completed' => ($submissionStatus !== 'draft')],
                ];
            @endphp

            @foreach($steps as $stepNumber => $step)
                @php
                    $isActive = ($stepNumber === $currentStep);
                    $isCompleted = $step['completed'];
                    $bgClass = $isActive ? 'bg-blue-500 text-white ring-4 ring-blue-200' : 
                              ($isCompleted ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600');
                @endphp

                <div class="flex flex-col items-center space-y-2 flex-1">
                    <div class="w-12 h-12 rounded-full {{ $bgClass }} flex items-center justify-center text-lg font-bold transition-all duration-300">
                        @if($isCompleted && !$isActive)
                            ✓
                        @else
                            {{ $stepNumber }}
                        @endif
                    </div>
                    <div class="text-center">
                        <div class="text-2xl mb-1">{{ $step['icon'] }}</div>
                        <div class="text-xs font-medium {{ $isActive ? 'text-blue-600' : ($isCompleted ? 'text-green-600' : 'text-gray-500') }}">
                            {{ $step['title'] }}
                        </div>
                    </div>
                </div>

                {{-- Add connector line between steps (except for last step) --}}
                @if($stepNumber < count($steps))
                    @php
                        $lineClass = ($steps[$stepNumber + 1]['completed']) ? 'bg-green-300' : 'bg-gray-300';
                    @endphp
                    <div class="flex-1 h-1 {{ $lineClass }} mx-4 mt-6 max-w-16 rounded transition-all duration-300"></div>
                @endif
            @endforeach
        </div>

        {{-- Current step description for desktop --}}
        <div class="text-center mt-4">
            @php
                $descriptions = [
                    1 => 'Informasi dasar pengajuan Anda sudah lengkap',
                    2 => 'Upload semua dokumen yang diperlukan',
                    3 => 'Review informasi dan kirim pengajuan untuk diproses'
                ];
            @endphp
            
            <p class="text-lg font-semibold text-blue-700">
                {{ $steps[$currentStep]['title'] }}
            </p>
            <p class="text-sm text-blue-600 mt-1">
                {{ $descriptions[$currentStep] }}
            </p>
        </div>
    </div>

    {{-- Status indicators for elderly users --}}
    <div class="mt-4 pt-4 border-t border-blue-200">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
            <div class="flex items-center space-x-2">
                <span class="text-green-500 text-lg">✅</span>
                <span class="text-green-700 font-medium">Informasi Lengkap</span>
            </div>
            
            <div class="flex items-center space-x-2">
                @if($isDocumentComplete)
                    <span class="text-green-500 text-lg">✅</span>
                    <span class="text-green-700 font-medium">Dokumen Lengkap</span>
                @else
                    <span class="text-orange-500 text-lg">⏳</span>
                    <span class="text-orange-700 font-medium">Dokumen Kurang</span>
                @endif
            </div>
            
            <div class="flex items-center space-x-2">
                @if($submissionStatus === 'submitted')
                    <span class="text-blue-500 text-lg">📤</span>
                    <span class="text-blue-700 font-medium">Sudah Dikirim</span>
                @elseif($submissionStatus === 'approved')
                    <span class="text-green-500 text-lg">🎉</span>
                    <span class="text-green-700 font-medium">Disetujui</span>
                @elseif($submissionStatus === 'revision_needed')
                    <span class="text-orange-500 text-lg">✏️</span>
                    <span class="text-orange-700 font-medium">Perlu Revisi</span>
                @else
                    <span class="text-gray-500 text-lg">📝</span>
                    <span class="text-gray-700 font-medium">Draft</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add custom CSS for better mobile experience --}}
<style>
    /* Ensure touch targets are large enough for elderly users */
    @media (max-width: 640px) {
        .filament-forms .fi-fo-component-ctn {
            margin-bottom: 1.5rem;
        }
        
        .filament-forms input,
        .filament-forms select,
        .filament-forms textarea {
            font-size: 16px !important;
            padding: 12px !important;
            border-radius: 8px !important;
        }
        
        .filament-forms .fi-btn {
            padding: 12px 24px !important;
            font-size: 16px !important;
            border-radius: 8px !important;
        }
    }
    
    /* High contrast for better visibility */
    .elderly-friendly {
        line-height: 1.6;
        font-size: 16px;
    }
    
    /* Larger tap targets */
    .fi-btn {
        min-height: 44px;
        min-width: 44px;
    }
</style>
