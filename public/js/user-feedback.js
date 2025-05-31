/**
 * Elderly-Friendly User Feedback Collection System
 * Collects usability feedback with accessibility considerations
 */

class UserFeedbackCollector {
    constructor() {
        this.isVisible = false;
        this.feedbackData = {};
        this.init();
    }

    init() {
        this.createFeedbackWidget();
        this.setupTriggers();
        this.trackUserBehavior();
    }

    createFeedbackWidget() {
        // Create floating feedback button
        const feedbackButton = document.createElement('div');
        feedbackButton.id = 'feedback-trigger';
        feedbackButton.innerHTML = `
            <button class="feedback-btn" onclick="userFeedbackCollector.showFeedbackForm()" 
                    aria-label="Berikan feedback tentang kemudahan penggunaan sistem">
                <span class="feedback-icon">💬</span>
                <span class="feedback-text">Feedback</span>
            </button>
        `;
        
        // Add styles
        const styles = `
            <style>
                #feedback-trigger {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 1000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                }
                
                .feedback-btn {
                    background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    padding: 12px 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    min-height: 48px; /* Accessibility: minimum touch target */
                }
                
                .feedback-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
                }
                
                .feedback-btn:focus {
                    outline: 3px solid #93C5FD;
                    outline-offset: 2px;
                }
                
                .feedback-icon {
                    font-size: 20px;
                }
                
                @media (max-width: 768px) {
                    #feedback-trigger {
                        bottom: 80px; /* Above mobile navigation if present */
                        right: 16px;
                    }
                    
                    .feedback-btn {
                        padding: 14px 16px;
                        border-radius: 50px;
                    }
                    
                    .feedback-text {
                        display: none; /* Hide text on mobile, keep icon */
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
        document.body.appendChild(feedbackButton);
    }

    showFeedbackForm() {
        if (this.isVisible) return;
        
        this.isVisible = true;
        
        const modal = document.createElement('div');
        modal.id = 'feedback-modal';
        modal.innerHTML = `
            <div class="feedback-overlay" onclick="userFeedbackCollector.hideFeedbackForm()"></div>
            <div class="feedback-modal" role="dialog" aria-labelledby="feedback-title" aria-modal="true">
                <div class="feedback-header">
                    <h2 id="feedback-title">Bagaimana pengalaman Anda menggunakan sistem ini?</h2>
                    <button class="feedback-close" onclick="userFeedbackCollector.hideFeedbackForm()" 
                            aria-label="Tutup formulir feedback">×</button>
                </div>
                
                <form id="feedback-form" class="feedback-form">
                    <!-- Overall Rating -->
                    <div class="feedback-group">
                        <label class="feedback-label">Seberapa mudah menggunakan sistem ini?</label>
                        <div class="rating-stars" role="radiogroup" aria-label="Rating kemudahan penggunaan">
                            ${this.createStarRating('rating')}
                        </div>
                    </div>
                    
                    <!-- Difficulty Areas -->
                    <div class="feedback-group">
                        <label class="feedback-label">Bagian mana yang sulit? (boleh pilih lebih dari satu)</label>
                        <div class="difficulty-options">
                            ${this.createDifficultyOptions()}
                        </div>
                    </div>
                    
                    <!-- Demographics (optional) -->
                    <div class="feedback-group">
                        <label class="feedback-label">Usia Anda (opsional)</label>
                        <select name="age_range" class="feedback-select">
                            <option value="">Pilih usia...</option>
                            <option value="under_30">Di bawah 30 tahun</option>
                            <option value="30_40">30-40 tahun</option>
                            <option value="40_50">40-50 tahun</option>
                            <option value="50_60">50-60 tahun</option>
                            <option value="60_70">60-70 tahun</option>
                            <option value="over_70">Di atas 70 tahun</option>
                        </select>
                    </div>
                    
                    <div class="feedback-group">
                        <label class="feedback-label">Seberapa nyaman Anda dengan teknologi?</label>
                        <select name="tech_comfort" class="feedback-select">
                            <option value="">Pilih tingkat kenyamanan...</option>
                            <option value="expert">Sangat mahir</option>
                            <option value="advanced">Mahir</option>
                            <option value="intermediate">Cukup bisa</option>
                            <option value="beginner">Masih belajar</option>
                        </select>
                    </div>
                    
                    <div class="feedback-group">
                        <label class="feedback-label">Perangkat yang digunakan</label>
                        <select name="device_type" class="feedback-select">
                            <option value="">Pilih perangkat...</option>
                            <option value="desktop">Komputer desktop</option>
                            <option value="laptop">Laptop</option>
                            <option value="tablet">Tablet/iPad</option>
                            <option value="mobile">Ponsel</option>
                        </select>
                    </div>
                    
                    <!-- Comments -->
                    <div class="feedback-group">
                        <label for="comments" class="feedback-label">Saran atau keluhan (opsional)</label>
                        <textarea name="comments" id="comments" rows="4" 
                                placeholder="Ceritakan pengalaman Anda atau berikan saran untuk perbaikan..."
                                class="feedback-textarea"></textarea>
                    </div>
                    
                    <!-- Contact Permission -->
                    <div class="feedback-group">
                        <label class="feedback-checkbox-label">
                            <input type="checkbox" name="contact_permission" class="feedback-checkbox">
                            <span class="checkmark"></span>
                            Saya bersedia dihubungi untuk penjelasan lebih lanjut
                        </label>
                    </div>
                    
                    <div class="feedback-actions">
                        <button type="button" onclick="userFeedbackCollector.hideFeedbackForm()" 
                                class="feedback-btn-secondary">Batal</button>
                        <button type="submit" class="feedback-btn-primary">Kirim Feedback</button>
                    </div>
                </form>
            </div>
        `;
        
        // Add modal styles
        this.addModalStyles();
        document.body.appendChild(modal);
        
        // Setup form submission
        document.getElementById('feedback-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitFeedback();
        });
        
        // Focus management for accessibility
        setTimeout(() => {
            document.getElementById('feedback-title').focus();
        }, 100);
    }

    createStarRating(name) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += `
                <button type="button" class="star-btn" 
                        onclick="userFeedbackCollector.setRating(${i})"
                        onkeydown="userFeedbackCollector.handleStarKeydown(event, ${i})"
                        aria-label="${i} dari 5 bintang"
                        role="radio"
                        aria-checked="false"
                        data-rating="${i}">
                    <span class="star">⭐</span>
                </button>
            `;
        }
        return html;
    }

    createDifficultyOptions() {
        const options = [
            { value: 'navigation', label: 'Berpindah halaman/navigasi' },
            { value: 'forms', label: 'Mengisi formulir' },
            { value: 'upload', label: 'Upload dokumen' },
            { value: 'understanding', label: 'Memahami instruksi' },
            { value: 'text_size', label: 'Teks terlalu kecil' },
            { value: 'buttons', label: 'Tombol sulit diklik' }
        ];
        
        return options.map(option => `
            <label class="difficulty-option">
                <input type="checkbox" name="difficult_areas" value="${option.value}" class="difficulty-checkbox">
                <span class="difficulty-checkmark"></span>
                <span class="difficulty-label">${option.label}</span>
            </label>
        `).join('');
    }

    setRating(rating) {
        this.feedbackData.rating = rating;
        
        // Update visual state
        document.querySelectorAll('.star-btn').forEach((btn, index) => {
            const isSelected = index < rating;
            btn.classList.toggle('selected', isSelected);
            btn.setAttribute('aria-checked', isSelected);
            btn.querySelector('.star').style.opacity = isSelected ? '1' : '0.3';
        });
    }

    handleStarKeydown(event, rating) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.setRating(rating);
        } else if (event.key === 'ArrowLeft' && rating > 1) {
            event.preventDefault();
            document.querySelector(`[data-rating="${rating - 1}"]`).focus();
        } else if (event.key === 'ArrowRight' && rating < 5) {
            event.preventDefault();
            document.querySelector(`[data-rating="${rating + 1}"]`).focus();
        }
    }

    async submitFeedback() {
        const form = document.getElementById('feedback-form');
        const formData = new FormData(form);
        
        // Collect form data
        const data = {
            rating: this.feedbackData.rating || 5,
            difficult_areas: formData.getAll('difficult_areas'),
            age_range: formData.get('age_range'),
            tech_comfort: formData.get('tech_comfort'),
            device_type: formData.get('device_type'),
            comments: formData.get('comments'),
            contact_permission: formData.has('contact_permission'),
            page_url: window.location.href,
            user_agent: navigator.userAgent
        };
        
        // Validate required fields
        if (!data.rating) {
            this.showError('Mohon berikan rating terlebih dahulu');
            return;
        }
        
        try {
            this.showLoading(true);
            
            const response = await fetch('/api/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Terima kasih! Feedback Anda telah terkirim.');
                setTimeout(() => this.hideFeedbackForm(), 2000);
            } else {
                this.showError(result.message || 'Terjadi kesalahan. Silakan coba lagi.');
            }
        } catch (error) {
            console.error('Feedback submission error:', error);
            this.showError('Terjadi kesalahan jaringan. Silakan coba lagi.');
        } finally {
            this.showLoading(false);
        }
    }

    showLoading(show) {
        const submitBtn = document.querySelector('.feedback-btn-primary');
        if (show) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Mengirim...';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Kirim Feedback';
        }
    }

    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    showError(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        const existing = document.querySelector('.feedback-message');
        if (existing) existing.remove();
        
        const messageEl = document.createElement('div');
        messageEl.className = `feedback-message feedback-message-${type}`;
        messageEl.textContent = message;
        
        const form = document.querySelector('.feedback-form');
        form.insertBefore(messageEl, form.firstChild);
        
        setTimeout(() => messageEl.remove(), 5000);
    }

    hideFeedbackForm() {
        const modal = document.getElementById('feedback-modal');
        if (modal) {
            modal.remove();
            this.isVisible = false;
        }
    }

    trackUserBehavior() {
        // Track time spent on page
        this.pageStartTime = Date.now();
        
        // Track scroll behavior
        let maxScroll = 0;
        window.addEventListener('scroll', () => {
            const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            maxScroll = Math.max(maxScroll, scrollPercent);
        });
        
        // Track click patterns for accessibility issues
        let clickCount = 0;
        document.addEventListener('click', () => {
            clickCount++;
        });
        
        // Store behavior data
        this.behaviorData = {
            getTimeOnPage: () => Date.now() - this.pageStartTime,
            getMaxScroll: () => maxScroll,
            getClickCount: () => clickCount
        };
    }

    setupTriggers() {
        // Show feedback form after user has been on page for a while
        setTimeout(() => {
            if (!this.isVisible && !localStorage.getItem('feedback_shown_today')) {
                this.showFeedbackPrompt();
            }
        }, 60000); // 1 minute
        
        // Show on exit intent (desktop only)
        if (window.innerWidth > 768) {
            document.addEventListener('mouseleave', (e) => {
                if (e.clientY <= 0 && !this.isVisible && !localStorage.getItem('feedback_dismissed_today')) {
                    this.showFeedbackPrompt();
                }
            });
        }
    }

    showFeedbackPrompt() {
        // Simple prompt asking if user wants to give feedback
        const prompt = document.createElement('div');
        prompt.id = 'feedback-prompt';
        prompt.innerHTML = `
            <div class="feedback-prompt">
                <p>Apakah sistem ini mudah digunakan? Bantu kami dengan feedback singkat!</p>
                <div class="prompt-actions">
                    <button onclick="userFeedbackCollector.dismissPrompt()" class="prompt-btn-secondary">Nanti saja</button>
                    <button onclick="userFeedbackCollector.acceptPrompt()" class="prompt-btn-primary">Ya, berikan feedback</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(prompt);
        localStorage.setItem('feedback_shown_today', Date.now());
    }

    acceptPrompt() {
        document.getElementById('feedback-prompt')?.remove();
        this.showFeedbackForm();
    }

    dismissPrompt() {
        document.getElementById('feedback-prompt')?.remove();
        localStorage.setItem('feedback_dismissed_today', Date.now());
    }

    addModalStyles() {
        if (document.getElementById('feedback-modal-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'feedback-modal-styles';
        styles.textContent = `
            .feedback-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .feedback-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                max-width: 600px;
                width: 90vw;
                max-height: 80vh;
                overflow-y: auto;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            }
            
            .feedback-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 24px 24px 16px;
                border-bottom: 1px solid #E5E7EB;
            }
            
            .feedback-header h2 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
                color: #1F2937;
            }
            
            .feedback-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6B7280;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
            }
            
            .feedback-close:hover {
                background: #F3F4F6;
            }
            
            .feedback-form {
                padding: 24px;
            }
            
            .feedback-group {
                margin-bottom: 24px;
            }
            
            .feedback-label {
                display: block;
                font-size: 16px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 12px;
            }
            
            .rating-stars {
                display: flex;
                gap: 8px;
                margin-bottom: 8px;
            }
            
            .star-btn {
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                border-radius: 8px;
                transition: all 0.2s;
                min-height: 48px;
                min-width: 48px;
            }
            
            .star-btn:hover, .star-btn:focus {
                background: #F3F4F6;
                outline: 2px solid #3B82F6;
            }
            
            .star {
                font-size: 28px;
                opacity: 0.3;
                transition: opacity 0.2s;
            }
            
            .star-btn.selected .star {
                opacity: 1;
            }
            
            .difficulty-options {
                display: grid;
                gap: 12px;
            }
            
            .difficulty-option {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border: 2px solid #E5E7EB;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
                min-height: 48px;
            }
            
            .difficulty-option:hover {
                border-color: #3B82F6;
                background: #F8FAFC;
            }
            
            .difficulty-checkbox {
                width: 20px;
                height: 20px;
            }
            
            .difficulty-label {
                font-size: 16px;
                color: #374151;
            }
            
            .feedback-select, .feedback-textarea {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #E5E7EB;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.2s;
                min-height: 48px;
            }
            
            .feedback-select:focus, .feedback-textarea:focus {
                outline: none;
                border-color: #3B82F6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            .feedback-textarea {
                resize: vertical;
                font-family: inherit;
            }
            
            .feedback-checkbox-label {
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
                font-size: 16px;
                color: #374151;
                min-height: 48px;
            }
            
            .feedback-checkbox {
                width: 20px;
                height: 20px;
            }
            
            .feedback-actions {
                display: flex;
                gap: 16px;
                justify-content: flex-end;
                margin-top: 32px;
                padding-top: 24px;
                border-top: 1px solid #E5E7EB;
            }
            
            .feedback-btn-primary, .feedback-btn-secondary {
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                min-height: 48px;
                min-width: 120px;
                border: 2px solid transparent;
            }
            
            .feedback-btn-primary {
                background: #3B82F6;
                color: white;
            }
            
            .feedback-btn-primary:hover:not(:disabled) {
                background: #2563EB;
            }
            
            .feedback-btn-primary:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            .feedback-btn-secondary {
                background: white;
                color: #374151;
                border-color: #E5E7EB;
            }
            
            .feedback-btn-secondary:hover {
                background: #F9FAFB;
                border-color: #D1D5DB;
            }
            
            .feedback-message {
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 500;
            }
            
            .feedback-message-success {
                background: #ECFDF5;
                color: #065F46;
                border: 1px solid #A7F3D0;
            }
            
            .feedback-message-error {
                background: #FEF2F2;
                color: #991B1B;
                border: 1px solid #FECACA;
            }
            
            .loading-spinner {
                width: 16px;
                height: 16px;
                border: 2px solid #ffffff;
                border-top: 2px solid transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                display: inline-block;
                margin-right: 8px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            #feedback-prompt {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 350px;
            }
            
            .feedback-prompt {
                background: white;
                border: 1px solid #E5E7EB;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }
            
            .feedback-prompt p {
                margin: 0 0 16px;
                font-size: 16px;
                color: #374151;
            }
            
            .prompt-actions {
                display: flex;
                gap: 12px;
            }
            
            .prompt-btn-primary, .prompt-btn-secondary {
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                border: 1px solid transparent;
                min-height: 36px;
            }
            
            .prompt-btn-primary {
                background: #3B82F6;
                color: white;
            }
            
            .prompt-btn-secondary {
                background: white;
                color: #6B7280;
                border-color: #E5E7EB;
            }
            
            @media (max-width: 768px) {
                .feedback-modal {
                    width: 95vw;
                    max-height: 90vh;
                    margin: 0;
                    border-radius: 12px;
                }
                
                .feedback-header, .feedback-form {
                    padding: 16px;
                }
                
                .feedback-actions {
                    flex-direction: column;
                }
                
                .feedback-btn-primary, .feedback-btn-secondary {
                    width: 100%;
                }
                
                #feedback-prompt {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.userFeedbackCollector = new UserFeedbackCollector();
});

// Clean up localStorage daily
if (localStorage.getItem('feedback_shown_today')) {
    const shown = parseInt(localStorage.getItem('feedback_shown_today'));
    if (Date.now() - shown > 24 * 60 * 60 * 1000) { // 24 hours
        localStorage.removeItem('feedback_shown_today');
        localStorage.removeItem('feedback_dismissed_today');
    }
}
