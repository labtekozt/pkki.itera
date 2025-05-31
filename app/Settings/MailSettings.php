<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    public string $from_address;
    public string $from_name;
    public ?string $driver;
    public ?string $host;
    public int $port;
    public string $encryption;
    public ?string $username;
    public ?string $password;
    public ?int $timeout;
    public ?string $local_domain;

    public static function group(): string
    {
        return 'mail';
    }

    public static function encrypted(): array
    {
        return [
            'username',
            'password',
        ];
    }

    public function loadMailSettingsToConfig($data = null): void
    {
        // Only load non-null values to prevent overriding working .env config
        $config = [];
        
        $host = $data['host'] ?? $this->host;
        $port = $data['port'] ?? $this->port;
        $encryption = $data['encryption'] ?? $this->encryption;
        $username = $data['username'] ?? $this->username;
        $password = $data['password'] ?? $this->password;
        $fromAddress = $data['from_address'] ?? $this->from_address;
        $fromName = $data['from_name'] ?? $this->from_name;
        
        if ($host) {
            $config['mail.mailers.smtp.host'] = $host;
        }
        
        if ($port) {
            $config['mail.mailers.smtp.port'] = $port;
        }
        
        if ($encryption) {
            $config['mail.mailers.smtp.encryption'] = $encryption;
        }
        
        if ($username) {
            $config['mail.mailers.smtp.username'] = $username;
        }
        
        if ($password) {
            $config['mail.mailers.smtp.password'] = $password;
        }
        
        if ($fromAddress) {
            $config['mail.from.address'] = $fromAddress;
        }
        
        if ($fromName) {
            $config['mail.from.name'] = $fromName;
        }
        
        if (!empty($config)) {
            config($config);
        }
    }

    /**
     * Check if MailSettings is configured with necessary values.
     */
    public function isMailSettingsConfigured(): bool
    {
        // Check if the essential fields are not null
        return $this->host && $this->username && $this->password && $this->from_address;
    }

    /**
     * Get the current mail configuration status for debugging.
     */
    public function getConfigurationStatus(): array
    {
        return [
            'host_configured' => !empty($this->host),
            'username_configured' => !empty($this->username),
            'password_configured' => !empty($this->password),
            'from_address_configured' => !empty($this->from_address),
            'from_name_configured' => !empty($this->from_name),
            'port' => $this->port,
            'encryption' => $this->encryption,
            'fully_configured' => $this->isMailSettingsConfigured(),
        ];
    }
}
