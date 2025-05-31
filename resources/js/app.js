// PKKI ITERA Frontend JavaScript
import './bootstrap';

// Elderly-friendly UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add accessibility improvements for elderly users
    
    // Focus management for keyboard navigation
    const focusableElements = document.querySelectorAll(
        'input, textarea, select, button, a[href], [tabindex]:not([tabindex="-1"])'
    );
    
    // Enhanced form validation feedback
    const forms = document.querySelectorAll('.elderly-friendly-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add loading state for better user feedback
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(button => {
                button.innerHTML = '⏳ Memproses...';
                button.disabled = true;
            });
        });
    });
    
    // File upload progress enhancement
    const fileUploads = document.querySelectorAll('.elderly-friendly-upload input[type="file"]');
    fileUploads.forEach(upload => {
        upload.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const feedback = document.createElement('div');
                feedback.className = 'text-green-600 text-sm mt-2';
                feedback.innerHTML = `✅ ${files.length} file(s) dipilih`;
                
                // Remove existing feedback
                const existingFeedback = e.target.parentNode.querySelector('.file-feedback');
                if (existingFeedback) {
                    existingFeedback.remove();
                }
                
                feedback.className += ' file-feedback';
                e.target.parentNode.appendChild(feedback);
            }
        });
    });
    
    // Progress indicator animations
    const progressSteps = document.querySelectorAll('.elderly-progress-step');
    progressSteps.forEach((step, index) => {
        step.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Smooth scrolling for section navigation
    const sectionHeaders = document.querySelectorAll('.elderly-section-header');
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const section = this.closest('.elderly-form-section');
            if (section) {
                section.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Enhanced button interactions
    const elderlyButtons = document.querySelectorAll('.elderly-friendly-button');
    elderlyButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Auto-save draft functionality (for longer forms)
    let autoSaveTimeout;
    const draftInputs = document.querySelectorAll('.elderly-friendly-form input, .elderly-friendly-form textarea');
    draftInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Show auto-save indicator
                const indicator = document.querySelector('.auto-save-indicator');
                if (indicator) {
                    indicator.innerHTML = '💾 Draft tersimpan otomatis';
                    indicator.style.opacity = '1';
                    setTimeout(() => {
                        indicator.style.opacity = '0';
                    }, 2000);
                }
            }, 3000);
        });
    });
    
    // Enhanced elderly-friendly features for EditSubmission page
    initAutoSave();
    initTooltips();
    initDocumentFeedbackEnhancements();
    initAccessibilityEnhancements();
    initMobileEnhancements();
});

/**
 * Auto-save functionality to prevent elderly users from losing work
 */
function initAutoSave() {
    const forms = document.querySelectorAll('.elderly-friendly-form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', debounce(function() {
                // Save to localStorage as backup
                const formData = new FormData(form);
                const dataObj = {};
                for (let [key, value] of formData.entries()) {
                    dataObj[key] = value;
                }
                localStorage.setItem('pkki_form_backup', JSON.stringify(dataObj));
                
                // Show auto-save indicator
                showAutoSaveIndicator();
            }, 2000));
        });
    });
    
    // Restore from backup if available
    restoreFromBackup();
}

/**
 * Enhanced tooltips with larger text and better positioning
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.getAttribute('data-tooltip'));
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
        
        // Also show tooltip on focus for keyboard users
        element.addEventListener('focus', function() {
            showTooltip(this, this.getAttribute('data-tooltip'));
        });
        
        element.addEventListener('blur', function() {
            hideTooltip();
        });
    });
}

/**
 * Document feedback integration enhancements
 */
function initDocumentFeedbackEnhancements() {
    // Add smooth scrolling to feedback sections
    const feedbackBoxes = document.querySelectorAll('[id^="integrated_feedback_"]');
    
    feedbackBoxes.forEach(box => {
        // Add expand/collapse functionality for long feedback
        const feedbackContent = box.querySelector('.feedback-content');
        if (feedbackContent && feedbackContent.scrollHeight > 150) {
            addExpandCollapseToFeedback(feedbackContent);
        }
    });
    
    // Highlight upload areas when feedback requires action
    highlightActionRequiredSections();
}

/**
 * Accessibility enhancements for elderly users
 */
function initAccessibilityEnhancements() {
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + H for help
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            scrollToHelp();
        }
        
        // Alt + S for save (submit)
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                submitButton.click();
            }
        }
    });
    
    // Enhanced focus indicators
    const focusableElements = document.querySelectorAll(
        'input, textarea, select, button, a[href], [tabindex]:not([tabindex="-1"])'
    );
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '3px solid #3b82f6';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
        });
    });
    
    // Add skip links for screen readers
    addSkipLinks();
}

/**
 * Mobile-specific enhancements
 */
function initMobileEnhancements() {
    if (window.innerWidth <= 768) {
        // Add mobile-specific touch enhancements
        const buttons = document.querySelectorAll('button, .contact-button-enhanced');
        
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
        
        // Improve file upload on mobile
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            // Add visual feedback for mobile file selection
            input.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const parent = this.closest('.fi-fo-file-upload');
                    if (parent) {
                        parent.style.borderColor = '#22c55e';
                        parent.style.backgroundColor = '#f0fdf4';
                    }
                }
            });
        });
    }
}

/**
 * Utility functions
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showAutoSaveIndicator() {
    let indicator = document.getElementById('auto-save-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'auto-save-indicator';
        indicator.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
        indicator.innerHTML = '✅ Tersimpan otomatis';
        document.body.appendChild(indicator);
    }
    
    indicator.style.opacity = '1';
    setTimeout(() => {
        indicator.style.opacity = '0';
    }, 2000);
}

function restoreFromBackup() {
    const backup = localStorage.getItem('pkki_form_backup');
    if (backup) {
        try {
            const data = JSON.parse(backup);
            Object.keys(data).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && !input.value) {
                    input.value = data[key];
                    // Trigger change event for reactive fields
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        } catch (e) {
            console.log('Could not restore form backup');
        }
    }
}

function showTooltip(element, text) {
    hideTooltip(); // Remove any existing tooltip
    
    const tooltip = document.createElement('div');
    tooltip.id = 'elderly-tooltip';
    tooltip.className = 'fixed bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg z-50 max-w-xs text-sm';
    tooltip.innerHTML = text;
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let top = rect.top - tooltipRect.height - 10;
    let left = rect.left + (rect.width - tooltipRect.width) / 2;
    
    // Adjust if tooltip goes off screen
    if (top < 10) {
        top = rect.bottom + 10;
    }
    if (left < 10) {
        left = 10;
    }
    if (left + tooltipRect.width > window.innerWidth - 10) {
        left = window.innerWidth - tooltipRect.width - 10;
    }
    
    tooltip.style.top = top + 'px';
    tooltip.style.left = left + 'px';
}

function hideTooltip() {
    const tooltip = document.getElementById('elderly-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

function addExpandCollapseToFeedback(feedbackContent) {
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'text-blue-600 hover:text-blue-800 text-sm font-medium mt-2';
    toggleButton.innerHTML = '📖 Lihat selengkapnya';
    
    feedbackContent.style.maxHeight = '150px';
    feedbackContent.style.overflow = 'hidden';
    
    toggleButton.addEventListener('click', function() {
        if (feedbackContent.style.maxHeight === '150px') {
            feedbackContent.style.maxHeight = 'none';
            this.innerHTML = '📕 Lihat lebih sedikit';
        } else {
            feedbackContent.style.maxHeight = '150px';
            this.innerHTML = '📖 Lihat selengkapnya';
        }
    });
    
    feedbackContent.parentNode.appendChild(toggleButton);
}

function highlightActionRequiredSections() {
    const revisionSections = document.querySelectorAll('.status-revision-needed, .status-rejected');
    
    revisionSections.forEach(section => {
        // Add pulsing animation to draw attention
        section.style.animation = 'pulse 2s infinite';
        
        // Add click handler to scroll to upload area
        const uploadArea = section.querySelector('.elderly-friendly-upload');
        if (uploadArea) {
            const scrollButton = document.createElement('button');
            scrollButton.type = 'button';
            scrollButton.className = 'inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 mt-3';
            scrollButton.innerHTML = '📤 Langsung ke Upload';
            
            scrollButton.addEventListener('click', function() {
                uploadArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                uploadArea.style.animation = 'highlight 1s ease-in-out';
            });
            
            const feedbackBox = section.querySelector('.feedback-box-enhanced');
            if (feedbackBox) {
                feedbackBox.appendChild(scrollButton);
            }
        }
    });
}

function scrollToHelp() {
    const helpSection = document.querySelector('[data-section="help"], .help-section-enhanced');
    if (helpSection) {
        helpSection.scrollIntoView({ behavior: 'smooth' });
        // Flash the help section to draw attention
        helpSection.style.animation = 'flash 1s ease-in-out';
    }
}

function addSkipLinks() {
    const skipLinks = document.createElement('div');
    skipLinks.className = 'sr-only focus-within:not-sr-only fixed top-0 left-0 z-50 bg-blue-600 text-white p-4';
    skipLinks.innerHTML = `
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <a href="#document-section" class="skip-link ml-4">Skip to documents</a>
        <a href="#help-section" class="skip-link ml-4">Skip to help</a>
    `;
    
    document.body.insertBefore(skipLinks, document.body.firstChild);
}

// Add CSS animations for enhanced interactions
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    
    @keyframes highlight {
        0% { background-color: transparent; }
        50% { background-color: rgba(59, 130, 246, 0.1); }
        100% { background-color: transparent; }
    }
    
    @keyframes flash {
        0%, 100% { background-color: transparent; }
        50% { background-color: rgba(16, 185, 129, 0.1); }
    }
    
    .skip-link {
        color: white !important;
        text-decoration: underline !important;
    }
    
    .skip-link:focus {
        outline: 2px solid white !important;
    }
`;
document.head.appendChild(style);

// Export for use in other modules
export default {
    version: '1.0.0',
    description: 'PKKI ITERA Elderly-Friendly UI Enhancements'
};
