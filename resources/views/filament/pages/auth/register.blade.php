<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/register.actions.login.before') }}

            {{ $this->loginAction }}
        </x-slot>
    @endif

    <style>
        .fi-simple-layout-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .fi-page-auth-register {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 50%, #db2777 100%);
            min-height: 100vh;
        }

        .fi-section {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .fi-section-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
        }

        .fi-section-header-heading {
            color: #1f2937;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .fi-section-description {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .fi-btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .fi-btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #8b5cf6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .fi-input {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }

        .fi-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        /* Custom header styling */
        .registration-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .registration-header h1 {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .registration-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .fi-simple-layout-content {
                margin: 1rem;
                border-radius: 12px;
            }
            
            .registration-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="registration-header">
        <h1>{{ $this->getHeading() }}</h1>
        <p>Selamat datang di Portal PKKI ITERA</p>
    </div>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
