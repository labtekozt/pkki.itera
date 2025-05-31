{{-- Custom Filament Admin Panel Layout with Accessibility Features --}}
@extends(filament()->getLayout())

{{-- Add accessibility toolbar right after body opens --}}
@push('after-open-body')
    @include('filament.components.accessibility-toolbar')
@endpush

{{-- Add user feedback widget to all admin pages --}}
@push('scripts')
    <script>
        // Auto-inject feedback widget on all admin pages
        document.addEventListener('DOMContentLoaded', function() {
            // Only show feedback widget on form pages or complex interfaces
            const isFormPage = document.querySelector('form') !== null;
            const isComplexPage = document.querySelector('[data-filament-resource]') !== null;
            
            if (isFormPage || isComplexPage) {
                // Create feedback widget container
                const feedbackContainer = document.createElement('div');
                feedbackContainer.innerHTML = `
                    @include('filament.components.user-feedback-widget', [
                        'page_url' => request()->url(),
                        'page_title' => isset($this->record) ? 'Edit ' . class_basename($this->record) : (isset($this->getTitle) ? $this->getTitle() : 'Admin Panel')
                    ])
                `;
                
                // Append to body
                document.body.appendChild(feedbackContainer.firstElementChild);
            }
        });
    </script>
@endpush

{{-- Add elderly-friendly CSS customizations --}}
@push('styles')
    <style>
        /* Enhanced contrast and sizing for elderly users */
        .fi-main {
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Larger buttons for easier clicking */
        .fi-btn {
            min-height: 44px;
            padding: 12px 20px;
            font-size: 16px;
        }
        
        /* Enhanced form field styling */
        .fi-input {
            font-size: 16px;
            padding: 12px;
            min-height: 44px;
        }
        
        /* Better focus indicators */
        .fi-input:focus,
        .fi-btn:focus {
            outline: 3px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Enhanced table styling for readability */
        .fi-ta-cell {
            padding: 12px;
            font-size: 14px;
        }
        
        /* Improved navigation */
        .fi-sidebar-nav-item {
            font-size: 16px;
            padding: 12px 16px;
        }
        
        /* High contrast mode support */
        .accessibility-high-contrast .fi-main {
            filter: contrast(150%) brightness(120%);
        }
        
        .accessibility-high-contrast .fi-sidebar {
            background: #000 !important;
            color: #fff !important;
        }
        
        .accessibility-high-contrast .fi-sidebar-nav-item {
            border-bottom: 1px solid #333;
        }
        
        /* Large font support */
        .accessibility-large-font .fi-main {
            font-size: 20px;
        }
        
        .accessibility-large-font .fi-btn {
            font-size: 18px;
            padding: 14px 24px;
        }
        
        .accessibility-large-font .fi-input {
            font-size: 18px;
            padding: 14px;
        }
        
        /* Extra large font support */
        .accessibility-extra-large-font .fi-main {
            font-size: 24px;
        }
        
        .accessibility-extra-large-font .fi-btn {
            font-size: 20px;
            padding: 16px 28px;
        }
        
        .accessibility-extra-large-font .fi-input {
            font-size: 20px;
            padding: 16px;
        }
        
        /* Reading guide line */
        .reading-guide-line {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #ff0000;
            z-index: 9999;
            pointer-events: none;
            transition: top 0.1s ease;
        }
        
        /* Loading states with better feedback */
        .fi-loading-overlay {
            background: rgba(0, 0, 0, 0.7);
        }
        
        .fi-loading-overlay::after {
            content: 'Sedang memuat...';
            color: white;
            font-size: 18px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: 40px;
        }
        
        /* Success/error message styling */
        .fi-no-notification {
            font-size: 16px;
            padding: 16px 20px;
        }
        
        /* Simplified wizard styling */
        .fi-wizard .fi-wizard-step {
            padding: 20px;
            margin: 10px 0;
        }
        
        .fi-wizard .fi-wizard-step-label {
            font-size: 18px;
            font-weight: 600;
        }
        
        /* Mobile-friendly enhancements */
        @media (max-width: 768px) {
            .fi-main {
                font-size: 18px;
            }
            
            .fi-btn {
                min-height: 48px;
                font-size: 18px;
            }
            
            .fi-input {
                font-size: 18px;
                min-height: 48px;
            }
            
            .fi-ta-cell {
                font-size: 16px;
                padding: 16px 12px;
            }
        }
    </style>
@endpush
