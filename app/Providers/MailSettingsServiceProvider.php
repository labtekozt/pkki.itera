<?php

namespace App\Providers;

use App\Settings\MailSettings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class MailSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only load mail settings if we're not in console mode (artisan commands)
        // and if the settings table exists (to avoid errors during migrations)
        if (!$this->app->runningInConsole() && $this->settingsTableExists()) {
            try {
                $mailSettings = app(MailSettings::class);
                
                // Only override config if database settings are configured
                if ($mailSettings->isMailSettingsConfigured()) {
                    $this->loadMailConfiguration($mailSettings);
                }
            } catch (\Exception $e) {
                // Silently fail if settings can't be loaded
                // This prevents the app from breaking if settings are corrupted
                \Log::warning('Failed to load mail settings from database: ' . $e->getMessage());
            }
        }
    }

    /**
     * Load mail configuration from MailSettings into Laravel config.
     */
    private function loadMailConfiguration(MailSettings $settings): void
    {
        $config = [];
        
        if ($settings->host) {
            $config['mail.mailers.smtp.host'] = $settings->host;
        }
        
        if ($settings->port) {
            $config['mail.mailers.smtp.port'] = $settings->port;
        }
        
        if ($settings->encryption) {
            $config['mail.mailers.smtp.encryption'] = $settings->encryption;
        }
        
        if ($settings->username) {
            $config['mail.mailers.smtp.username'] = $settings->username;
        }
        
        if ($settings->password) {
            $config['mail.mailers.smtp.password'] = $settings->password;
        }
        
        if ($settings->from_address) {
            $config['mail.from.address'] = $settings->from_address;
        }
        
        if ($settings->from_name) {
            $config['mail.from.name'] = $settings->from_name;
        }
        
        if ($settings->timeout) {
            $config['mail.mailers.smtp.timeout'] = $settings->timeout;
        }
        
        if ($settings->local_domain) {
            $config['mail.mailers.smtp.local_domain'] = $settings->local_domain;
        }
        
        // Set the default mailer to smtp if we have settings
        $config['mail.default'] = 'smtp';
        
        if (!empty($config)) {
            Config::set($config);
        }
    }

    /**
     * Check if the settings table exists in the database.
     */
    private function settingsTableExists(): bool
    {
        try {
            return \Schema::hasTable('settings');
        } catch (\Exception $e) {
            return false;
        }
    }
}
