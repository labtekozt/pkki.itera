{{-- User Feedback Widget for Elderly Users --}}
<div id="feedback-widget" class="fixed bottom-4 right-4 z-50">
    {{-- Feedback Button --}}
    <button id="feedback-btn" 
            onclick="openFeedbackModal()"
            class="bg-green-500 hover:bg-green-600 text-white p-3 rounded-full shadow-lg transition-all duration-300"
            title="Berikan Feedback">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-3.7-.8l-3.3.8 1-3.1c-1.1-1.4-1.8-3.2-1.8-5.1 0-4.418 3.582-8 8-8s8 3.582 8 8z">
            </path>
        </svg>
    </button>
</div>

{{-- Feedback Modal --}}
<div id="feedback-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-lg w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">💬 Bagikan Pengalaman Anda</h2>
                    <button onclick="closeFeedbackModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="feedback-form" onsubmit="submitFeedback(event)">
                    {{-- Experience Rating --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            🌟 Seberapa mudah menggunakan halaman ini?
                        </label>
                        <div class="flex space-x-2">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" 
                                        onclick="setRating({{ $i }})"
                                        id="star-{{ $i }}"
                                        class="star-btn text-3xl text-gray-300 hover:text-yellow-400 transition-colors"
                                        title="{{ $i }} bintang">
                                    ⭐
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" id="rating" name="rating" required>
                    </div>

                    {{-- Ease of Use Questions --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            📋 Bagian mana yang paling sulit?
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="navigation" class="mr-2">
                                <span>Navigasi/berpindah halaman</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="forms" class="mr-2">
                                <span>Mengisi formulir</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="upload" class="mr-2">
                                <span>Upload dokumen</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="understanding" class="mr-2">
                                <span>Memahami instruksi</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="text_size" class="mr-2">
                                <span>Ukuran teks terlalu kecil</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="difficult_areas[]" value="buttons" class="mr-2">
                                <span>Tombol sulit diklik</span>
                            </label>
                        </div>
                    </div>

                    {{-- Age Range --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            👥 Usia Anda (opsional)
                        </label>
                        <select name="age_range" class="w-full p-2 border border-gray-300 rounded">
                            <option value="">Pilih rentang usia</option>
                            <option value="under_30">Di bawah 30 tahun</option>
                            <option value="30_40">30-40 tahun</option>
                            <option value="40_50">40-50 tahun</option>
                            <option value="50_60">50-60 tahun</option>
                            <option value="60_70">60-70 tahun</option>
                            <option value="over_70">Di atas 70 tahun</option>
                        </select>
                    </div>

                    {{-- Technology Comfort --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            💻 Seberapa nyaman Anda dengan teknologi?
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="tech_comfort" value="very_comfortable" class="mr-2">
                                <span>Sangat nyaman - sering menggunakan komputer/smartphone</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tech_comfort" value="comfortable" class="mr-2">
                                <span>Cukup nyaman - kadang menggunakan</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tech_comfort" value="not_comfortable" class="mr-2">
                                <span>Kurang nyaman - jarang menggunakan</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tech_comfort" value="need_help" class="mr-2">
                                <span>Butuh bantuan - selalu minta tolong orang lain</span>
                            </label>
                        </div>
                    </div>

                    {{-- Device Used --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            📱 Perangkat yang digunakan saat ini
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="device_type" value="desktop" class="mr-2">
                                <span>Komputer/Laptop</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="device_type" value="tablet" class="mr-2">
                                <span>Tablet</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="device_type" value="smartphone" class="mr-2">
                                <span>Smartphone</span>
                            </label>
                        </div>
                    </div>

                    {{-- Additional Comments --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            💭 Saran atau komentar tambahan
                        </label>
                        <textarea name="comments" 
                                  rows="4" 
                                  class="w-full p-3 border border-gray-300 rounded"
                                  placeholder="Ceritakan pengalaman Anda atau saran untuk perbaikan..."></textarea>
                    </div>

                    {{-- Contact Permission --}}
                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" name="contact_permission" class="mr-2 mt-1">
                            <span class="text-sm text-gray-600">
                                Saya bersedia dihubungi tim PKKI ITERA untuk penelitian lebih lanjut tentang 
                                pengalaman pengguna (opsional)
                            </span>
                        </label>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded font-medium">
                            📤 Kirim Feedback
                        </button>
                        <button type="button" 
                                onclick="closeFeedbackModal()"
                                class="px-4 py-3 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                    </div>
                </form>

                {{-- Thank You Message --}}
                <div id="thank-you-message" class="hidden text-center">
                    <div class="text-6xl mb-4">🙏</div>
                    <h3 class="text-xl font-bold text-green-600 mb-2">Terima Kasih!</h3>
                    <p class="text-gray-600 mb-4">
                        Feedback Anda sangat berharga untuk membantu kami membuat sistem yang lebih mudah digunakan.
                    </p>
                    <button onclick="closeFeedbackModal()" 
                            class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentRating = 0;

function openFeedbackModal() {
    document.getElementById('feedback-modal').classList.remove('hidden');
    
    // Voice feedback
    if (typeof speak === 'function' && isVoiceEnabled) {
        speak('Membuka formulir feedback. Silakan berikan penilaian pengalaman Anda.');
    }
}

function closeFeedbackModal() {
    document.getElementById('feedback-modal').classList.add('hidden');
    document.getElementById('feedback-form').classList.remove('hidden');
    document.getElementById('thank-you-message').classList.add('hidden');
    
    // Reset form
    document.getElementById('feedback-form').reset();
    resetStars();
}

function setRating(rating) {
    currentRating = rating;
    document.getElementById('rating').value = rating;
    
    // Update star display
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById(`star-${i}`);
        if (i <= rating) {
            star.classList.add('text-yellow-400');
            star.classList.remove('text-gray-300');
        } else {
            star.classList.add('text-gray-300');
            star.classList.remove('text-yellow-400');
        }
    }
    
    // Voice feedback
    if (typeof speak === 'function' && isVoiceEnabled) {
        const ratingText = rating === 1 ? 'sangat sulit' : 
                          rating === 2 ? 'sulit' : 
                          rating === 3 ? 'cukup' : 
                          rating === 4 ? 'mudah' : 'sangat mudah';
        speak(`Anda memberikan penilaian ${rating} bintang - ${ratingText}`);
    }
}

function resetStars() {
    currentRating = 0;
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById(`star-${i}`);
        star.classList.add('text-gray-300');
        star.classList.remove('text-yellow-400');
    }
}

function submitFeedback(event) {
    event.preventDefault();
    
    if (currentRating === 0) {
        alert('Mohon berikan penilaian bintang terlebih dahulu');
        return;
    }
    
    const formData = new FormData(event.target);
    const feedbackData = {
        rating: currentRating,
        difficult_areas: formData.getAll('difficult_areas[]'),
        age_range: formData.get('age_range'),
        tech_comfort: formData.get('tech_comfort'),
        device_type: formData.get('device_type'),
        comments: formData.get('comments'),
        contact_permission: formData.get('contact_permission') === 'on',
        page_url: window.location.href,
        timestamp: new Date().toISOString(),
        user_agent: navigator.userAgent
    };
    
    // Send feedback to server
    fetch('/api/user-feedback', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify(feedbackData)
    })
    .then(response => response.json())
    .then(data => {
        // Show thank you message
        document.getElementById('feedback-form').classList.add('hidden');
        document.getElementById('thank-you-message').classList.remove('hidden');
        
        // Voice feedback
        if (typeof speak === 'function' && isVoiceEnabled) {
            speak('Terima kasih! Feedback Anda telah berhasil dikirim.');
        }
        
        // Auto close after 3 seconds
        setTimeout(() => {
            closeFeedbackModal();
        }, 3000);
    })
    .catch(error => {
        console.error('Error submitting feedback:', error);
        alert('Maaf, terjadi kesalahan saat mengirim feedback. Silakan coba lagi.');
    });
}

// Show feedback widget after user has been on page for 30 seconds
setTimeout(() => {
    const feedbackBtn = document.getElementById('feedback-btn');
    feedbackBtn.classList.add('animate-pulse');
    
    // Remove pulse after 5 seconds
    setTimeout(() => {
        feedbackBtn.classList.remove('animate-pulse');
    }, 5000);
}, 30000);

// Track user interaction patterns
let interactionData = {
    clicks: 0,
    scrolls: 0,
    timeOnPage: Date.now(),
    errors: []
};

document.addEventListener('click', () => interactionData.clicks++);
document.addEventListener('scroll', () => interactionData.scrolls++);

// Track form errors
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('invalid', (e) => {
            interactionData.errors.push({
                field: e.target.name,
                message: e.target.validationMessage,
                timestamp: Date.now()
            });
        }, true);
    });
});
</script>

<style>
.star-btn {
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.star-btn:hover {
    transform: scale(1.1);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.animate-pulse {
    animation: pulse 2s infinite;
}
</style>
