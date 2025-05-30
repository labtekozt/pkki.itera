<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
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

        .fi-page-auth-login {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 50%, #db2777 100%);
            min-height: 100vh;
        }

        .fi-btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            border: none;
            font-weight: 600;
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
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Registration link styling */
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .register-link a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .register-link a:hover {
            color: #6d28d9;
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .fi-simple-layout-content {
                margin: 1rem;
                border-radius: 12px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="login-header">
        <h1>Selamat Datang</h1>
        <p>Portal PKKI ITERA</p>
    </div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @if (filament()->hasRegistration())
        <div class="register-link">
            <p>Belum memiliki akun? 
                <a href="{{ filament()->getRegistrationUrl() }}">
                    Daftar di sini
                </a>
            </p>
        </div>
    @endif
</x-filament-panels::page.simple>
