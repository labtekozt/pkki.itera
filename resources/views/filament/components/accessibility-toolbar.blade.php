{{-- Accessibility Toolbar for Elderly Users --}}
<div id="accessibility-toolbar" class="fixed top-0 left-0 right-0 z-50 bg-white border-b-2 border-blue-200 shadow-lg">
    <div class="container mx-auto px-4 py-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            {{-- Font Size Controls --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Ukuran Teks:</span>
                <button onclick="changeFontSize('small')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Teks Kecil">
                    A-
                </button>
                <button onclick="changeFontSize('normal')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Teks Normal">
                    A
                </button>
                <button onclick="changeFontSize('large')" 
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Teks Besar">
                    A+
                </button>
            </div>

            {{-- High Contrast Toggle --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Kontras Tinggi:</span>
                <button onclick="toggleHighContrast()" 
                        id="contrast-toggle"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Aktif/Nonaktifkan Kontras Tinggi">
                    🎨 Kontras
                </button>
            </div>

            {{-- Reading Guide --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Panduan Baca:</span>
                <button onclick="toggleReadingGuide()" 
                        id="reading-guide-toggle"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Tampilkan/Sembunyikan Panduan Baca">
                    📏 Panduan
                </button>
            </div>

            {{-- Voice Instructions --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Suara:</span>
                <button onclick="toggleVoiceInstructions()" 
                        id="voice-toggle"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100"
                        title="Aktifkan/Nonaktifkan Instruksi Suara">
                    🔊 Voice
                </button>
            </div>

            {{-- Help Button --}}
            <button onclick="showAccessibilityHelp()" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    title="Bantuan Aksesibilitas">
                ❓ Bantuan
            </button>
        </div>
    </div>
</div>

{{-- Reading Guide Line --}}
<div id="reading-guide" class="fixed pointer-events-none z-40 w-full h-1 bg-red-500 opacity-50 hidden" style="top: 50%;"></div>

{{-- Accessibility Help Modal --}}
<div id="accessibility-help-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">🛠️ Bantuan Aksesibilitas</h2>
                    <button onclick="hideAccessibilityHelp()" class="text-gray-500 hover:text-gray-700">
                        ✕
                    </button>
                </div>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <h3 class="font-semibold text-blue-600">📝 Navigasi Keyboard:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Tab: Pindah ke elemen berikutnya</li>
                            <li>Shift + Tab: Pindah ke elemen sebelumnya</li>
                            <li>Enter/Space: Aktifkan tombol atau link</li>
                            <li>Escape: Tutup modal atau dialog</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-green-600">🎨 Kontras & Ukuran:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Gunakan A+/A- untuk memperbesar/memperkecil teks</li>
                            <li>Toggle kontras tinggi untuk visibilitas lebih baik</li>
                            <li>Panduan baca membantu fokus pada baris tertentu</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-purple-600">🔊 Fitur Suara:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Aktifkan instruksi suara untuk panduan audio</li>
                            <li>Browser akan membacakan petunjuk penting</li>
                        </ul>
                    </div>
                    
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <p class="text-blue-800">
                            <strong>💡 Tips:</strong> Jika mengalami kesulitan, hubungi admin di 0811-7348-927 
                            atau email pkki@itera.ac.id untuk bantuan langsung.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Font size classes */
.font-small { font-size: 14px !important; }
.font-normal { font-size: 16px !important; }
.font-large { font-size: 18px !important; }

/* High contrast mode */
.high-contrast {
    filter: contrast(150%) brightness(120%);
}

.high-contrast input,
.high-contrast select,
.high-contrast textarea,
.high-contrast button {
    border: 2px solid #000 !important;
    background: #fff !important;
    color: #000 !important;
}

.high-contrast .bg-blue-50 { background: #e6f3ff !important; }
.high-contrast .bg-green-50 { background: #e6ffe6 !important; }
.high-contrast .bg-yellow-50 { background: #fffde6 !important; }
.high-contrast .bg-red-50 { background: #ffe6e6 !important; }

/* Focus indicators for keyboard navigation */
.high-contrast *:focus {
    outline: 3px solid #ff0000 !important;
    outline-offset: 2px !important;
}

/* Reading guide positioning */
body.reading-guide-active #reading-guide {
    display: block !important;
}
</style>

<script>
let isHighContrast = false;
let isReadingGuide = false;
let isVoiceEnabled = false;

// Font size management
function changeFontSize(size) {
    const body = document.body;
    body.classList.remove('font-small', 'font-normal', 'font-large');
    body.classList.add(`font-${size}`);
    
    // Store preference
    localStorage.setItem('accessibility-font-size', size);
    
    // Voice feedback
    if (isVoiceEnabled) {
        speak(`Ukuran teks diubah ke ${size === 'large' ? 'besar' : size === 'small' ? 'kecil' : 'normal'}`);
    }
}

// High contrast toggle
function toggleHighContrast() {
    isHighContrast = !isHighContrast;
    const body = document.body;
    
    if (isHighContrast) {
        body.classList.add('high-contrast');
        document.getElementById('contrast-toggle').textContent = '🎨 AKTIF';
    } else {
        body.classList.remove('high-contrast');
        document.getElementById('contrast-toggle').textContent = '🎨 NONAKTIF';
    }
    
    localStorage.setItem('accessibility-high-contrast', isHighContrast);
    
    if (isVoiceEnabled) {
        speak(isHighContrast ? 'Kontras tinggi diaktifkan' : 'Kontras tinggi dinonaktifkan');
    }
}

// Reading guide
function toggleReadingGuide() {
    isReadingGuide = !isReadingGuide;
    const body = document.body;
    const button = document.getElementById('reading-guide-toggle');
    
    if (isReadingGuide) {
        body.classList.add('reading-guide-active');
        button.textContent = '📏 ON';
        // Add mouse move listener
        document.addEventListener('mousemove', updateReadingGuide);
    } else {
        body.classList.remove('reading-guide-active');
        button.textContent = '📏 OFF';
        document.removeEventListener('mousemove', updateReadingGuide);
    }
    
    localStorage.setItem('accessibility-reading-guide', isReadingGuide);
}

function updateReadingGuide(e) {
    const guide = document.getElementById('reading-guide');
    guide.style.top = e.clientY + 'px';
}

// Voice instructions
function toggleVoiceInstructions() {
    isVoiceEnabled = !isVoiceEnabled;
    const button = document.getElementById('voice-toggle');
    
    if (isVoiceEnabled) {
        button.textContent = '🔊 ON';
        speak('Instruksi suara diaktifkan. Sistem akan memberikan panduan audio.');
    } else {
        button.textContent = '🔊 OFF';
        speechSynthesis.cancel(); // Stop any current speech
    }
    
    localStorage.setItem('accessibility-voice-enabled', isVoiceEnabled);
}

function speak(text) {
    if (!isVoiceEnabled || !('speechSynthesis' in window)) return;
    
    speechSynthesis.cancel(); // Cancel any ongoing speech
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID'; // Indonesian language
    utterance.rate = 0.8; // Slower speech rate for elderly users
    utterance.pitch = 1.0;
    
    speechSynthesis.speak(utterance);
}

// Help modal functions
function showAccessibilityHelp() {
    document.getElementById('accessibility-help-modal').classList.remove('hidden');
    if (isVoiceEnabled) {
        speak('Menampilkan bantuan aksesibilitas');
    }
}

function hideAccessibilityHelp() {
    document.getElementById('accessibility-help-modal').classList.add('hidden');
}

// Load saved preferences
function loadAccessibilityPreferences() {
    const fontSize = localStorage.getItem('accessibility-font-size') || 'normal';
    const highContrast = localStorage.getItem('accessibility-high-contrast') === 'true';
    const readingGuide = localStorage.getItem('accessibility-reading-guide') === 'true';
    const voiceEnabled = localStorage.getItem('accessibility-voice-enabled') === 'true';
    
    changeFontSize(fontSize);
    
    if (highContrast) {
        isHighContrast = false;
        toggleHighContrast();
    }
    
    if (readingGuide) {
        isReadingGuide = false;
        toggleReadingGuide();
    }
    
    if (voiceEnabled) {
        isVoiceEnabled = false;
        toggleVoiceInstructions();
    }
}

// Initialize accessibility features
document.addEventListener('DOMContentLoaded', function() {
    loadAccessibilityPreferences();
    
    // Add keyboard navigation hints
    if (isVoiceEnabled) {
        speak('Halaman dimuat. Gunakan Tab untuk navigasi atau aktifkan bantuan dengan menekan tombol bantuan.');
    }
    
    // Enhance focus management
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
});

// Enhanced focus styles for keyboard navigation
const focusStyle = document.createElement('style');
focusStyle.textContent = `
    .keyboard-navigation *:focus {
        outline: 2px solid #2563eb !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2) !important;
    }
`;
document.head.appendChild(focusStyle);
</script>
