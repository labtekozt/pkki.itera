{{-- Certificate Status Component for Elderly-Friendly UI --}}
<div class="certificate-status-container">
    @if($certificateExists && isset($certificateMetadata['file_exists']) && $certificateMetadata['file_exists'])
        {{-- Certificate Available Section --}}
        <div class="bg-white rounded-2xl border-2 border-green-300 shadow-lg overflow-hidden">
            {{-- Header with celebration --}}
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <div class="flex items-center">
                    <div class="bg-white rounded-full p-3 mr-4">
                        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">🎉 Selamat! Sertifikat Sudah Tersedia</h3>
                        <p class="text-green-100 text-sm">Pengajuan Anda telah berhasil diselesaikan</p>
                    </div>
                </div>
            </div>

            {{-- Certificate Information --}}
            <div class="p-6 space-y-4">
                {{-- Certificate Number --}}
                @if(isset($certificateMetadata['certificate_number']))
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">🔢</span>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Nomor Sertifikat</p>
                            <p class="text-lg font-bold text-gray-900">{{ $certificateMetadata['certificate_number'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Issue Date --}}
                @if(isset($certificateMetadata['issued_date']))
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">📅</span>
                        <div>
                            <p class="text-sm font-medium text-blue-600">Tanggal Terbit</p>
                            <p class="text-lg font-semibold text-blue-900">
                                {{ \Carbon\Carbon::parse($certificateMetadata['issued_date'])->format('d F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- File Information --}}
                @if(isset($certificateMetadata['file_size']))
                <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">📁</span>
                        <div>
                            <p class="text-sm font-medium text-purple-600">Informasi File</p>
                            <p class="text-lg font-semibold text-purple-900">PDF • {{ $certificateMetadata['file_size'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Download Instructions --}}
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-200">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">💡</span>
                        <div>
                            <p class="text-sm font-medium text-amber-600 mb-2">Cara Mengunduh Sertifikat:</p>
                            <ol class="text-sm text-amber-800 space-y-1">
                                <li>1. Klik tombol "📥 Unduh Sertifikat" di bawah</li>
                                <li>2. Tunggu beberapa detik hingga unduhan dimulai</li>
                                <li>3. File akan tersimpan di folder "Download" perangkat Anda</li>
                                <li>4. Buka file dengan aplikasi pembaca PDF</li>
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Large Download Button - Elderly Friendly --}}
                <div class="pt-4">
                    <button 
                        wire:click="downloadCertificate"
                        wire:confirm="Apakah Anda yakin ingin mengunduh sertifikat ini?"
                        class="w-full bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-bold py-6 px-8 rounded-2xl shadow-lg transform transition-all duration-200 hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-green-300"
                    >
                        <div class="flex items-center justify-center">
                            <svg class="h-8 w-8 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-2xl">📥 Unduh Sertifikat</span>
                        </div>
                        <p class="text-green-100 text-sm mt-2">Klik untuk menyimpan sertifikat ke perangkat Anda</p>
                    </button>
                </div>

                {{-- Alternative Contact Info --}}
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mt-4">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">📞</span>
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Butuh Bantuan?</p>
                            <p class="text-sm text-gray-700">
                                Jika mengalami kesulitan mengunduh, silakan hubungi Tim PKKI ITERA melalui:
                            </p>
                            <p class="text-sm text-blue-600 font-medium mt-1">
                                📧 Email: pkki@itera.ac.id | 📱 WhatsApp: (0721) 8030188
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @elseif($certificateExists && (!isset($certificateMetadata['file_exists']) || !$certificateMetadata['file_exists']))
        {{-- Certificate Exists but File Missing --}}
        <div class="bg-white rounded-2xl border-2 border-red-300 shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                <div class="flex items-center">
                    <div class="bg-white rounded-full p-3 mr-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">⚠️ File Sertifikat Tidak Tersedia</h3>
                        <p class="text-red-100 text-sm">Terjadi masalah dengan file sertifikat Anda</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="bg-red-50 rounded-xl p-4 border border-red-200">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">🚨</span>
                        <div>
                            <p class="text-sm font-medium text-red-600 mb-2">Apa yang terjadi?</p>
                            <p class="text-sm text-red-800 mb-3">
                                Pengajuan Anda telah selesai dan sertifikat telah diterbitkan, namun file sertifikat tidak dapat diakses saat ini.
                            </p>
                            <p class="text-sm font-medium text-red-600 mb-2">Apa yang harus dilakukan?</p>
                            <p class="text-sm text-red-800">
                                Silakan hubungi Tim PKKI ITERA untuk mendapatkan salinan sertifikat Anda.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Contact Information --}}
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 mt-4">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">📞</span>
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-1">Hubungi Tim PKKI ITERA</p>
                            <div class="space-y-2 text-sm text-blue-800">
                                <p>📧 Email: pkki@itera.ac.id</p>
                                <p>📱 WhatsApp: (0721) 8030188</p>
                                <p>🏢 Kantor: Gedung Rektorat ITERA, Lantai 2</p>
                                <p>🕒 Jam Kerja: Senin-Jumat, 08:00-16:00 WIB</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- No Certificate Yet --}}
        <div class="bg-white rounded-2xl border-2 border-blue-300 shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <div class="flex items-center">
                    <div class="bg-white rounded-full p-3 mr-4">
                        <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">⏳ Sertifikat Sedang Diproses</h3>
                        <p class="text-blue-100 text-sm">Pengajuan Anda masih dalam tahap penyelesaian</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">📋</span>
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-2">Status Saat Ini:</p>
                            <p class="text-sm text-blue-800 mb-3">
                                Pengajuan Anda sedang dalam proses review final. Sertifikat akan tersedia setelah semua tahap verifikasi selesai.
                            </p>
                            <p class="text-sm font-medium text-blue-600 mb-2">Yang Perlu Anda Lakukan:</p>
                            <ol class="text-sm text-blue-800 space-y-1">
                                <li>✅ Tunggu notifikasi email dari tim PKKI ITERA</li>
                                <li>✅ Periksa halaman ini secara berkala untuk update status</li>
                                <li>✅ Pastikan data kontak Anda masih aktif</li>
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Estimated Timeline --}}
                <div class="bg-green-50 rounded-xl p-4 border border-green-200 mt-4">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">⏰</span>
                        <div>
                            <p class="text-sm font-medium text-green-600 mb-1">Estimasi Waktu Penyelesaian</p>
                            <p class="text-sm text-green-800">
                                Sertifikat biasanya akan tersedia dalam <strong>3-7 hari kerja</strong> setelah semua persyaratan terpenuhi.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Contact for Questions --}}
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mt-4">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">❓</span>
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Ada Pertanyaan?</p>
                            <p class="text-sm text-gray-700">
                                Jika Anda memiliki pertanyaan tentang status pengajuan, silakan hubungi:
                            </p>
                            <p class="text-sm text-blue-600 font-medium mt-1">
                                📧 pkki@itera.ac.id | 📱 (0721) 8030188
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
/* Additional elderly-friendly styling */
.certificate-status-container {
    font-size: 16px;
    line-height: 1.6;
}

.certificate-status-container button:focus {
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.3);
}

.certificate-status-container p,
.certificate-status-container li {
    margin-bottom: 0.5rem;
}

/* High contrast for better readability */
.certificate-status-container .text-gray-900 {
    color: #1f2937;
}

.certificate-status-container .text-gray-700 {
    color: #374151;
}

/* Larger touch targets for mobile/elderly users */
@media (max-width: 768px) {
    .certificate-status-container button {
        padding: 1.5rem 2rem;
        font-size: 1.25rem;
    }
    
    .certificate-status-container .text-2xl {
        font-size: 1.5rem;
    }
}
</style>
