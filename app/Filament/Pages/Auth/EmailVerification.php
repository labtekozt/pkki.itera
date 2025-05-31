<?php

namespace App\Filament\Pages\Auth;

use App\Settings\MailSettings;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;

class EmailVerification extends EmailVerificationPrompt
{

    /**
     * @var string
     */
    protected static string $view = 'filament-panels::pages.auth.email-verification.email-verification-prompt';

    public function resendNotificationAction(): Action
    {
        return Action::make('resendNotification')
            ->link()
            ->label(__('filament-panels::pages/auth/email-verification/email-verification-prompt.actions.resend_notification.label') . '.')
            ->action(function (): void {
                try {
                    $this->rateLimit(2);
                } catch (TooManyRequestsException $exception) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled.title', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]))
                        ->body(array_key_exists('body', __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled') ?: []) ? __('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resend_throttled.body', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]) : null)
                        ->danger()
                        ->send();

                    return;
                }

                $user = Filament::auth()->user();

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

                $notification = new VerifyEmail();
                $notification->url = Filament::getVerifyEmailUrl($user);

                // Try to load mail settings if available and properly configured
                try {
                    $settings = app(MailSettings::class);
                    if ($settings && method_exists($settings, 'loadMailSettingsToConfig') && $settings->isMailSettingsConfigured()) {
                        $settings->loadMailSettingsToConfig();
                    }
                } catch (\Exception $e) {
                    // Continue without custom mail settings if they're not configured
                    // This allows email verification to work with default Laravel mail config
                }

                $user->notify($notification);

                Notification::make()
                    ->title(__('filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resent.title', [], 'Email verification link sent'))
                    ->success()
                    ->send();
            });
    }

    public function getTitle(): string | Htmlable
    {
        return __('filament::pages.auth.email-verification.title', ['Email Verification']);
    }

    public function getHeading(): string | Htmlable
    {
        return __('filament::pages.auth.email-verification.heading', ['Verify Email Address']);
    }
}
