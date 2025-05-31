<?php

namespace App\Console\Commands;

use App\Settings\MailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class TestMailConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {--email= : Test email address to send to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mail configuration and debug mail settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Mail Configuration...');
        $this->newLine();

        // Check environment variables
        $this->line('📋 Environment Variables:');
        $this->table(['Variable', 'Value'], [
            ['MAIL_MAILER', config('mail.default')],
            ['MAIL_HOST', config('mail.mailers.smtp.host')],
            ['MAIL_PORT', config('mail.mailers.smtp.port')],
            ['MAIL_USERNAME', config('mail.mailers.smtp.username') ? '***configured***' : 'Not set'],
            ['MAIL_PASSWORD', config('mail.mailers.smtp.password') ? '***configured***' : 'Not set'],
            ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption')],
            ['MAIL_FROM_ADDRESS', config('mail.from.address')],
            ['MAIL_FROM_NAME', config('mail.from.name')],
        ]);

        $this->newLine();

        // Check MailSettings configuration
        try {
            $mailSettings = app(MailSettings::class);
            $this->line('🗃️  Database Mail Settings:');
            
            $status = $mailSettings->getConfigurationStatus();
            $this->table(['Setting', 'Configured'], [
                ['Host', $status['host_configured'] ? '✅ Yes' : '❌ No'],
                ['Username', $status['username_configured'] ? '✅ Yes' : '❌ No'],
                ['Password', $status['password_configured'] ? '✅ Yes' : '❌ No'],
                ['From Address', $status['from_address_configured'] ? '✅ Yes' : '❌ No'],
                ['From Name', $status['from_name_configured'] ? '✅ Yes' : '❌ No'],
                ['Port', $status['port']],
                ['Encryption', $status['encryption']],
                ['Fully Configured', $status['fully_configured'] ? '✅ Yes' : '❌ No'],
            ]);

            if (!$status['fully_configured']) {
                $this->warn('⚠️  Database mail settings are not fully configured. Using .env defaults.');
            } else {
                $this->info('✅ Database mail settings are configured. Testing settings load...');
                $mailSettings->loadMailSettingsToConfig();
                $this->info('✅ Mail settings loaded successfully.');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error loading MailSettings: {$e->getMessage()}");
        }

        $this->newLine();

        // Test email sending if requested
        if ($this->option('email')) {
            $this->testEmailSending($this->option('email'));
        } else {
            $this->line('💡 To test email sending, use: php artisan mail:test --email=your@email.com');
        }
    }

    private function testEmailSending(string $email): void
    {
        $this->line('📧 Testing Email Sending...');
        
        try {
            Mail::raw('This is a test email from PKKI ITERA system.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('PKKI ITERA - Mail Configuration Test');
            });

            $this->info("✅ Test email sent successfully to {$email}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send test email: {$e->getMessage()}");
        }
    }
}
