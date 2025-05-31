<div class="space-y-6">
    <div class="prose prose-sm max-w-none">
        <p class="text-gray-600">
            Panduan ini menjelaskan arti setiap status dokumen dan tindakan yang mungkin perlu Anda lakukan.
        </p>
    </div>

    {{-- Status Definitions --}}
    <div class="space-y-4">
        {{-- Pending Status --}}
        <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Pending
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">Menunggu Peninjauan</h4>
                <p class="mt-1 text-sm text-gray-600">
                    Dokumen Anda telah diunggah dan menunggu penilaian dari peninjau. Tidak ada tindakan yang diperlukan dari Anda saat ini.
                </p>
            </div>
        </div>

        {{-- Approved Status --}}
        <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Approved
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-green-900">Dokumen Disetujui</h4>
                <p class="mt-1 text-sm text-green-700">
                    Dokumen Anda memenuhi semua persyaratan dan telah disetujui. Tidak diperlukan tindakan lebih lanjut untuk dokumen ini.
                </p>
            </div>
        </div>

        {{-- Revision Needed Status --}}
        <div class="flex items-start space-x-3 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Revision Needed
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-yellow-900">Perlu Revisi</h4>
                <p class="mt-1 text-sm text-yellow-700">
                    Dokumen Anda memiliki masalah kecil yang perlu diperbaiki. Tinjau masukan dan unggah versi yang telah direvisi.
                </p>
                <div class="mt-2">
                    <p class="text-xs font-medium text-yellow-800">Tindakan Diperlukan:</p>
                    <ul class="mt-1 text-xs text-yellow-700 list-disc list-inside">
                        <li>Tinjau masukan spesifik yang diberikan</li>
                        <li>Buat perubahan yang diminta</li>
                        <li>Unggah dokumen yang telah direvisi</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Rejected Status --}}
        <div class="flex items-start space-x-3 p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Rejected
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-red-900">Dokumen Ditolak</h4>
                <p class="mt-1 text-sm text-red-700">
                    Dokumen Anda memiliki masalah yang signifikan dan perlu diganti sepenuhnya. Tinjau masukan dengan seksama.
                </p>
                <div class="mt-2">
                    <p class="text-xs font-medium text-red-800">Tindakan Diperlukan:</p>
                    <ul class="mt-1 text-xs text-red-700 list-disc list-inside">
                        <li>Tinjau dengan seksama semua alasan penolakan</li>
                        <li>Buat dokumen baru yang mengatasi semua masalah</li>
                        <li>Unggah dokumen yang telah diperbaiki</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Replaced Status --}}
        <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Replaced
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-blue-900">Dokumen Diganti</h4>
                <p class="mt-1 text-sm text-blue-700">
                    Dokumen ini telah digantikan oleh versi yang lebih baru. Disimpan untuk referensi historis.
                </p>
            </div>
        </div>

        {{-- Final Status --}}
        <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Final
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-green-900">Versi Final</h4>
                <p class="mt-1 text-sm text-green-700">
                    Ini adalah versi final yang disetujui dari dokumen. Tidak akan ada perubahan lebih lanjut yang diterima.
                </p>
            </div>
        </div>
    </div>

    {{-- Tips Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-blue-900 mb-3 flex items-center">
            @svg('heroicon-o-light-bulb', 'h-4 w-4 mr-2')
            Tips untuk Peninjauan Dokumen yang Sukses
        </h4>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Klik "Lihat Masukan" untuk melihat komentar peninjau yang detail
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Pastikan dokumen memenuhi semua persyaratan format dan konten
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Tanggapi semua poin masukan sebelum mengirim ulang
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Hubungi dukungan jika Anda memerlukan klarifikasi tentang masukan
            </li>
        </ul>
    </div>
</div>
