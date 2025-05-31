{{-- Interface selection guide component --}}
<div class="bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-xl p-6 mb-6">
    <div class="text-center mb-4">
        <div class="text-3xl mb-2">🤔</div>
        <h3 class="text-xl font-bold text-gray-900">Pilih Interface yang Sesuai untuk Anda</h3>
        <p class="text-gray-600 text-sm">Kami menyediakan dua pilihan interface untuk kenyamanan Anda</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Simple Interface Card --}}
        <div class="bg-white border-2 border-green-300 rounded-lg p-4 hover:shadow-lg transition-shadow">
            <div class="text-center mb-3">
                <div class="text-4xl mb-2">😊</div>
                <h4 class="text-lg font-semibold text-green-800">Interface Mudah</h4>
                <p class="text-sm text-green-600">Khusus untuk kemudahan pengguna</p>
            </div>
            
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm text-green-700">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Langkah-langkah yang jelas dengan panduan</span>
                </div>
                <div class="flex items-center text-sm text-green-700">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Tombol dan teks yang lebih besar</span>
                </div>
                <div class="flex items-center text-sm text-green-700">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Bahasa yang mudah dipahami</span>
                </div>
                <div class="flex items-center text-sm text-green-700">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Visual indikator yang jelas</span>
                </div>
                <div class="flex items-center text-sm text-green-700">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Bantuan dan tips di setiap langkah</span>
                </div>
            </div>

            <div class="bg-green-50 border border-green-200 rounded p-3 mb-3">
                <p class="text-xs text-green-700">
                    <strong>Cocok untuk:</strong> Pengguna yang baru pertama kali, lebih suka interface sederhana, 
                    atau membutuhkan panduan langkah demi langkah.
                </p>
            </div>

            @if(in_array($record->status, ['draft', 'revision_needed']))
                <a href="{{ $resource::getUrl('edit-simple', ['record' => $record]) }}" 
                   class="block w-full bg-green-500 hover:bg-green-600 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors">
                    📝 Gunakan Interface Mudah
                </a>
            @else
                <div class="block w-full bg-gray-300 text-gray-500 text-center py-2 px-4 rounded-lg font-medium">
                    📝 Interface Mudah (Tidak Tersedia)
                </div>
            @endif
        </div>

        {{-- Advanced Interface Card --}}
        <div class="bg-white border-2 border-blue-300 rounded-lg p-4 hover:shadow-lg transition-shadow">
            <div class="text-center mb-3">
                <div class="text-4xl mb-2">🔧</div>
                <h4 class="text-lg font-semibold text-blue-800">Interface Lengkap</h4>
                <p class="text-sm text-blue-600">Untuk pengguna berpengalaman</p>
            </div>
            
            <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm text-blue-700">
                    <span class="text-blue-500 mr-2">✓</span>
                    <span>Semua fitur dalam satu halaman</span>
                </div>
                <div class="flex items-center text-sm text-blue-700">
                    <span class="text-blue-500 mr-2">✓</span>
                    <span>Interface yang familiar dengan sistem</span>
                </div>
                <div class="flex items-center text-sm text-blue-700">
                    <span class="text-blue-500 mr-2">✓</span>
                    <span>Akses langsung ke detail teknis</span>
                </div>
                <div class="flex items-center text-sm text-blue-700">
                    <span class="text-blue-500 mr-2">✓</span>
                    <span>Kontrol penuh atas semua pengaturan</span>
                </div>
                <div class="flex items-center text-sm text-blue-700">
                    <span class="text-blue-500 mr-2">✓</span>
                    <span>Efisien untuk editing massal</span>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-3">
                <p class="text-xs text-blue-700">
                    <strong>Cocok untuk:</strong> Administrator, staff IT, atau pengguna yang sudah 
                    familiar dengan sistem dan membutuhkan akses ke semua fitur.
                </p>
            </div>

            @if(in_array($record->status, ['draft', 'revision_needed']))
                <a href="{{ $resource::getUrl('edit', ['record' => $record]) }}" 
                   class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors">
                    🔧 Gunakan Interface Lengkap
                </a>
            @else
                <div class="block w-full bg-gray-300 text-gray-500 text-center py-2 px-4 rounded-lg font-medium">
                    🔧 Interface Lengkap (Tidak Tersedia)
                </div>
            @endif
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">
                <strong>💡 Tidak yakin mana yang cocok?</strong> 
                Coba Interface Mudah terlebih dahulu - Anda selalu bisa beralih ke Interface Lengkap kapan saja.
            </p>
            <div class="flex justify-center space-x-4 text-xs">
                <span class="flex items-center text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                    Direkomendasikan untuk pemula
                </span>
                <span class="flex items-center text-blue-600">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                    Untuk pengguna berpengalaman
                </span>
            </div>
        </div>
    </div>
</div>
